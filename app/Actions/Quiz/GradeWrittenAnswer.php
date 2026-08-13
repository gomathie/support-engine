<?php

namespace App\Actions\Quiz;

use App\Models\QuizAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records an examiner's mark on one written answer, and finalises the attempt
 * once nothing is left unmarked.
 */
class GradeWrittenAnswer
{
    public function __construct(
        private readonly FinaliseQuizAttempt $finalise,
    ) {}

    public function handle(
        QuizAnswer $answer,
        User $grader,
        int $points,
        ?string $feedback = null,
    ): QuizAnswer {
        return DB::transaction(function () use ($answer, $grader, $points, $feedback): QuizAnswer {
            $max = $answer->question->points;

            if ($points < 0 || $points > $max) {
                throw ValidationException::withMessages([
                    'points' => "Award between 0 and {$max} points for this question.",
                ]);
            }

            $answer->forceFill([
                'points_awarded' => $points,

                // "Correct" is a blunt flag for a question marked out of five;
                // treat anything at or above half marks as a pass for the
                // per-question indicator, and let the points carry the detail.
                'is_correct' => $max > 0 && $points >= ($max / 2),

                'grader_feedback' => $feedback,
                'graded_by' => $grader->id,
                'graded_at' => now(),
            ])->save();

            $attempt = $answer->attempt->refresh();

            // Finalise only when every answer on the attempt has been marked.
            $outstanding = $attempt->answers()->whereNull('graded_at')->count();

            if ($outstanding === 0) {
                $this->finalise->handle($attempt, $grader);
            }

            return $answer->refresh();
        });
    }
}
