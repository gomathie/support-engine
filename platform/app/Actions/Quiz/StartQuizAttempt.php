<?php

namespace App\Actions\Quiz;

use App\Enums\AttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartQuizAttempt
{
    public function handle(User $user, Quiz $quiz): QuizAttempt
    {
        return DB::transaction(function () use ($user, $quiz): QuizAttempt {
            // Resume rather than start again: a refreshed tab or a dropped
            // connection should not burn one of a limited number of attempts.
            $existing = $quiz->attempts()
                ->where('user_id', $user->id)
                ->where('status', AttemptStatus::InProgress->value)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->hasExpired()) {
                    // Grading an expired attempt rather than discarding it means
                    // the time limit costs the attempt, which is the point of
                    // having one.
                    app(GradeQuizAttempt::class)->handle($existing);
                } else {
                    return $existing;
                }
            }

            $used = $quiz->attemptsUsedBy($user);

            if ($quiz->max_attempts !== null && $used >= $quiz->max_attempts) {
                throw ValidationException::withMessages([
                    'quiz' => 'You have used all '.$quiz->max_attempts.' attempts for this quiz.',
                ]);
            }

            return $quiz->attempts()->create([
                'user_id' => $user->id,
                'course_id' => $quiz->course_id,
                'attempt_number' => $used + 1,
                'status' => AttemptStatus::InProgress,

                // Snapshot the pass mark. If an administrator raises it tomorrow,
                // an attempt sat today is still judged by today's rules.
                'passing_score' => $quiz->passing_score,

                'started_at' => now(),
            ]);
        });
    }
}
