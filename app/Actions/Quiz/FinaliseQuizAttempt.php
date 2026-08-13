<?php

namespace App\Actions\Quiz;

use App\Actions\Progress\CompleteLesson;
use App\Actions\Progress\RecalculateCourseProgress;
use App\Enums\AttemptStatus;
use App\Enums\CompletionRequirement;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Turns a fully-marked attempt into a score, a pass or fail, and the progress
 * that follows from it.
 *
 * Called straight from GradeQuizAttempt for a purely objective quiz, and from
 * GradeWrittenAnswer once the last written answer has been marked.
 */
class FinaliseQuizAttempt
{
    public function __construct(
        private readonly RecalculateCourseProgress $recalculate,
        private readonly CompleteLesson $completeLesson,
    ) {}

    public function handle(QuizAttempt $attempt, ?User $reviewer = null): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $reviewer): QuizAttempt {
            $earned = (int) $attempt->answers()->sum('points_awarded');
            $possible = (int) $attempt->points_possible;

            $score = $possible > 0 ? round($earned / $possible * 100, 2) : 0.0;

            // The pass mark snapshotted when the attempt started, not the
            // quiz's current setting — raising it later must not retroactively
            // fail somebody who already sat it.
            $passMark = $attempt->passing_score ?? $attempt->quiz->passing_score;

            $attempt->forceFill([
                'status' => AttemptStatus::Completed,
                'points_earned' => $earned,
                'score' => $score,
                'passed' => $score >= $passMark,
                'completed_at' => $attempt->completed_at ?? now(),
                'reviewed_at' => $reviewer ? now() : $attempt->reviewed_at,
                'reviewed_by' => $reviewer?->id ?? $attempt->reviewed_by,
            ])->save();

            $attempt->refresh();

            // A passing attempt can complete the lesson it is attached to, and
            // in either case the course rollup needs recomputing.
            if ($attempt->passed && $attempt->quiz->lesson_id) {
                $lesson = $attempt->quiz->lesson;

                if ($lesson && $lesson->completion_requirement === CompletionRequirement::Quiz) {
                    $this->completeLesson->handle($attempt->user, $lesson);

                    return $attempt->refresh();
                }
            }

            $this->recalculate->handle($attempt->user, $attempt->course);

            return $attempt->refresh();
        });
    }
}
