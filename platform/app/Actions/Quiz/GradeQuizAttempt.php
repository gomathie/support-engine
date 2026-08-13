<?php

namespace App\Actions\Quiz;

use App\Actions\Progress\CompleteLesson;
use App\Actions\Progress\RecalculateCourseProgress;
use App\Enums\AttemptStatus;
use App\Enums\CompletionRequirement;
use App\Enums\QuestionType;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Scores an attempt.
 *
 * Everything here happens server-side by design. The browser only ever sends
 * option ids and free text; it is never told which option is correct, and it
 * plays no part in deciding the score.
 */
class GradeQuizAttempt
{
    public function __construct(
        private readonly RecalculateCourseProgress $recalculate,
        private readonly CompleteLesson $completeLesson,
    ) {}

    /**
     * @param  array<int, array{question_id: int, option_ids?: array<int, int>, text?: string|null}>  $submissions
     */
    public function handle(QuizAttempt $attempt, array $submissions = []): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $submissions): QuizAttempt {
            $quiz = $attempt->quiz;

            $questions = $quiz->questions()->with('options')->get()->keyBy('id');

            $byQuestion = collect($submissions)->keyBy('question_id');

            $pointsPossible = 0;
            $pointsEarned = 0;

            foreach ($questions as $question) {
                $pointsPossible += $question->points;

                $submission = $byQuestion->get($question->id);

                // An unanswered question scores zero but is still recorded, so
                // the attempt review shows every question rather than only the
                // ones that were reached.
                $selectedIds = array_values(array_map(
                    'intval',
                    $submission['option_ids'] ?? [],
                ));
                $text = $submission['text'] ?? null;

                $isCorrect = $this->isCorrect($question, $selectedIds, $text);
                $awarded = $isCorrect ? $question->points : 0;
                $pointsEarned += $awarded;

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
                        'answered_at' => now(),
                    ],
                );
            }

            $score = $pointsPossible > 0
                ? round($pointsEarned / $pointsPossible * 100, 2)
                : 0.0;

            // The snapshot taken at start time, not the quiz's current setting.
            $passMark = $attempt->passing_score ?? $quiz->passing_score;

            $attempt->forceFill([
                'status' => AttemptStatus::Completed,
                'points_earned' => $pointsEarned,
                'points_possible' => $pointsPossible,
                'score' => $score,
                'passed' => $score >= $passMark,
                'completed_at' => now(),
            ])->save();

            // A passing attempt can complete the lesson it is attached to, and
            // in either case the course rollup needs recomputing.
            if ($attempt->passed && $quiz->lesson_id) {
                $lesson = $quiz->lesson;

                if ($lesson && $lesson->completion_requirement === CompletionRequirement::Quiz) {
                    $this->completeLesson->handle($attempt->user, $lesson);

                    return $attempt->refresh();
                }
            }

            $this->recalculate->handle($attempt->user, $attempt->course);

            return $attempt->refresh();
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

        $given = collect($selectedIds)->unique()->sort()->values()->all();

        if ($given === []) {
            return false;
        }

        // Multiple-choice is all-or-nothing: every correct option and no
        // incorrect ones. Partial credit would let somebody tick every box and
        // score.
        return $given === $correctIds;
    }

    private function normalise(string $value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($value))) ?? '';
    }
}
