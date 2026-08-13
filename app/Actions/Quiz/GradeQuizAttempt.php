<?php

namespace App\Actions\Quiz;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Marks an attempt.
 *
 * Objective questions are scored here and now. Written ones cannot be — they go
 * to an examiner, and the attempt sits at `pending_review` until every one has
 * been marked, at which point FinaliseQuizAttempt computes the real score.
 *
 * Everything happens server-side by design. The browser only ever sends option
 * ids and free text; it is never told which option is correct, and it plays no
 * part in deciding the score.
 */
class GradeQuizAttempt
{
    public function __construct(
        private readonly FinaliseQuizAttempt $finalise,
    ) {}

    /**
     * @param  array<int, array{question_id: int, option_ids?: array<int, int>, text?: string|null}>  $submissions
     */
    public function handle(QuizAttempt $attempt, array $submissions = []): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $submissions): QuizAttempt {
            $questions = $attempt->quiz->questions()->with('options')->get();
            $byQuestion = collect($submissions)->keyBy('question_id');

            $autoPoints = 0;
            $autoPossible = 0;
            $manualPossible = 0;

            foreach ($questions as $question) {
                $submission = $byQuestion->get($question->id);

                // Unanswered questions are still recorded, so the review screen
                // shows every question rather than only the ones reached.
                $selectedIds = array_values(array_map('intval', $submission['option_ids'] ?? []));
                $text = $submission['text'] ?? null;

                $manual = $question->type->requiresManualGrading();

                if ($manual) {
                    $manualPossible += $question->points;
                } else {
                    $autoPossible += $question->points;
                }

                $isCorrect = $manual ? false : $this->isCorrect($question, $selectedIds, $text);
                $awarded = $isCorrect ? $question->points : 0;

                if (! $manual) {
                    $autoPoints += $awarded;
                }

                QuizAnswer::query()->updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'quiz_question_id' => $question->id,
                    ],
                    [
                        'selected_option_ids' => $selectedIds,
                        'text_answer' => $text,
                        'is_correct' => $isCorrect,
                        'points_awarded' => $awarded,

                        // Null marks it as "not yet looked at", which is what
                        // holds the attempt in review. Objective answers are
                        // settled the moment they are recorded.
                        'graded_at' => $manual ? null : now(),

                        'answered_at' => now(),
                    ],
                );
            }

            $attempt->forceFill([
                'auto_points_earned' => $autoPoints,
                'points_possible' => $autoPossible + $manualPossible,
                'manual_points_possible' => $manualPossible,
                'completed_at' => now(),
            ])->save();

            if ($manualPossible > 0) {
                // Deliberately no score yet: the objective half alone would read
                // as a final mark and a fail.
                $attempt->forceFill([
                    'status' => AttemptStatus::PendingReview,
                    'points_earned' => null,
                    'score' => null,
                    'passed' => null,
                ])->save();

                return $attempt->refresh();
            }

            return $this->finalise->handle($attempt->refresh());
        });
    }

    /**
     * @param  array<int, int>  $selectedIds
     */
    private function isCorrect(QuizQuestion $question, array $selectedIds, ?string $text): bool
    {
        if ($question->type === QuestionType::ShortAnswer) {
            if ($text === null || trim($text) === '') {
                return false;
            }

            $normalised = $this->normalise($text);

            return $question->options
                ->where('is_correct', true)
                ->contains(fn ($option) => $this->normalise($option->label) === $normalised);
        }

        $correctIds = $question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        // A question whose answer key was never set cannot be got right. It is
        // surfaced as "needs an answer key" in the admin rather than silently
        // marking everyone wrong for ever.
        if ($correctIds === []) {
            return false;
        }

        $given = collect($selectedIds)->unique()->sort()->values()->all();

        if ($given === []) {
            return false;
        }

        // All-or-nothing: every correct option and no incorrect ones. Partial
        // credit would let somebody tick every box and score.
        return $given === $correctIds;
    }

    private function normalise(string $value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($value))) ?? '';
    }
}
