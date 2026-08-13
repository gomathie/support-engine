<?php

namespace App\Actions\Progress;

use App\Actions\Certificates\IssueCertificate;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single place a course percentage is decided.
 *
 * The prototype recomputed this in the browser on every click, from an object
 * that never persisted. Here it is derived from the database, written to the
 * course_progress rollup, and treated as authoritative — nothing in Vue is
 * allowed to disagree with it.
 */
class RecalculateCourseProgress
{
    public function __construct(
        private readonly IssueCertificate $issueCertificate,
    ) {}

    public function handle(User $user, Course $course): CourseProgress
    {
        return DB::transaction(function () use ($user, $course): CourseProgress {
            // Lock the rollup so two concurrent lesson completions cannot both
            // read "9 of 10" and race to write it.
            $progress = CourseProgress::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first()
                ?? new CourseProgress([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ]);

            $totalLessons = $course->lessons()->where('is_published', true)->count();

            $completedLessons = $user->lessonProgress()
                ->where('course_id', $course->id)
                ->whereNotNull('completed_at')
                ->whereHas('lesson', fn ($q) => $q->where('is_published', true))
                ->count();

            $progress->total_lessons = $totalLessons;
            $progress->completed_lessons = min($completedLessons, $totalLessons);

            $progress->percentage = $totalLessons > 0
                ? round($progress->completed_lessons / $totalLessons * 100, 2)
                : 0;

            // ---------------------------------------------------- final quiz
            $finalQuiz = $course->finalQuiz()->where('is_published', true)->first();

            $quizSatisfied = true;
            if ($finalQuiz) {
                $best = $finalQuiz->bestAttemptFor($user);
                $progress->final_score = $best?->score;
                $progress->quiz_attempts_count = $finalQuiz->attemptsUsedBy($user);
                $quizSatisfied = $finalQuiz->passedBy($user);
            }

            // ------------------------------------------------------- status
            $lessonsSatisfied = $totalLessons > 0 && $progress->completed_lessons >= $totalLessons;
            $hasStarted = $progress->completed_lessons > 0 || $progress->quiz_attempts_count > 0;

            $progress->started_at ??= $hasStarted ? now() : null;
            $progress->last_activity_at = now();

            if ($lessonsSatisfied && $quizSatisfied) {
                $progress->status = ProgressStatus::Completed;
                $progress->completed_at ??= now();
            } else {
                $progress->completed_at = null;

                $progress->status = match (true) {
                    // Out of attempts on a final assessment they have not passed.
                    $finalQuiz !== null
                        && ! $quizSatisfied
                        && ! $finalQuiz->hasAttemptsRemainingFor($user) => ProgressStatus::Failed,

                    $this->isOverdue($user, $course) => ProgressStatus::Overdue,

                    $hasStarted => ProgressStatus::InProgress,

                    default => ProgressStatus::NotStarted,
                };
            }

            $progress->save();

            // Certificate issuance hangs off completion rather than off the
            // controller, so every path that can finish a course — a lesson tick,
            // a passing quiz, an admin backfill — issues one.
            if ($progress->status === ProgressStatus::Completed) {
                $this->issueCertificate->handle($user, $course, $progress);
            }

            return $progress;
        });
    }

    private function isOverdue(User $user, Course $course): bool
    {
        $dueAt = $course->enrollments()
            ->where('user_id', $user->id)
            ->value('due_at');

        return $dueAt !== null && now()->greaterThan($dueAt);
    }
}
