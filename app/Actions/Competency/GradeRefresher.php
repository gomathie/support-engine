<?php

namespace App\Actions\Competency;

use App\Enums\RefresherStatus;
use App\Models\QuizQuestion;
use App\Models\Refresher;
use Illuminate\Support\Facades\DB;

/**
 * Marks a refresher and records what it says about retention.
 *
 * Marked the same way and to the same standard as a quiz — all-or-nothing per
 * question, decided entirely server-side, with the browser only ever sending
 * option ids. Nothing here awards, revokes or gates anything: the level is
 * already held, and a poor refresher is a signal about the training rather
 * than a verdict on the person.
 */
class GradeRefresher
{
    /**
     * @param  array<int, array{question_id: int, option_ids?: array<int, int>}>  $submissions
     */
    public function handle(Refresher $refresher, array $submissions = []): Refresher
    {
        return DB::transaction(function () use ($refresher, $submissions): Refresher {
            $answers = $refresher->answers()->with('question.options')->get();
            $byQuestion = collect($submissions)->keyBy('question_id');

            $earned = 0;
            $possible = 0;

            foreach ($answers as $answer) {
                $question = $answer->question;

                // A question deleted between drawing and submitting. It cannot
                // be marked either way, so it is dropped from the denominator
                // rather than counted against the trainee.
                if (! $question) {
                    continue;
                }

                $submission = $byQuestion->get($question->getKey());
                $selected = array_values(array_map('intval', $submission['option_ids'] ?? []));

                $correct = $this->isCorrect($question, $selected);
                $points = $correct ? $question->points : 0;

                $possible += $question->points;
                $earned += $points;

                $answer->forceFill([
                    'selected_option_ids' => $selected,
                    'is_correct' => $correct,
                    'points_awarded' => $points,
                    'answered_at' => now(),
                ])->save();
            }

            $score = $possible > 0 ? round(($earned / $possible) * 100, 2) : null;

            $refresher->forceFill([
                'status' => RefresherStatus::Completed,
                'score' => $score,
                'completed_at' => now(),
            ])->save();

            // Written after the score, because it is derived from it.
            $refresher->forceFill([
                'retention' => $refresher->refresh()->retentionAgainstBaseline(),
            ])->save();

            return $refresher->refresh();
        });
    }

    /**
     * All-or-nothing, matching the quiz engine exactly.
     *
     * Divergence here would be worse than a bug: a trainee scoring 60% on a
     * refresher and 40% on the same questions in an exam, because the two were
     * marked to different rules, would make KPI 6 meaningless.
     *
     * @param  array<int, int>  $selectedIds
     */
    private function isCorrect(QuizQuestion $question, array $selectedIds): bool
    {
        $correctIds = $question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        // A question with no answer key cannot be got right — same as the quiz
        // engine, where it surfaces in the admin as needing one.
        if ($correctIds === []) {
            return false;
        }

        $given = collect($selectedIds)->unique()->sort()->values()->all();

        return $given !== [] && $given === $correctIds;
    }
}
