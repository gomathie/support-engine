<?php

namespace App\Actions\Progress;

use App\Actions\Certificates\IssueCertificate;
use App\Actions\Competency\AwardCompetencyLevel;
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
        private readonly AwardCompetencyLevel $awardCompetencyLevel,
    ) {}

    public function handle(User $user, Course $course): CourseProgress
    {
        return DB::transaction(function () use ($user, $course): CourseProgress {
            // Lock the rollup so two concurrent topic completions cannot both
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

            $totalLessons = $course->topics()->where('is_published', true)->count();

            $completedLessons = $user->topicProgress()
                ->where('course_id', $course->id)
                ->whereNotNull('completed_at')
                ->whereHas('topic', fn ($q) => $q->where('is_published', true))
                ->count();

            $progress->total_topics = $totalLessons;
            $progress->completed_topics = min($completedLessons, $totalLessons);

            $progress->percentage = $totalLessons > 0
                ? round($progress->completed_topics / $totalLessons * 100, 2)
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

            // ------------------------------------------ knowledge checks
            /*
             * Every topic ends with a knowledge check, and every one of them
             * has to be passed.
             *
             * These are module-scoped quizzes. Without this they were
             * decoration: a trainee could skip every check and still finish the
             * course on the final exam alone, which makes "you must pass" untrue
             * for the thing sitting at the end of each topic.
             */
            $knowledgeChecks = $course->quizzes()
                ->whereNotNull('lesson_id')
                ->whereNull('topic_id')
                ->where('is_published', true)
                ->get();

            $checksSatisfied = $knowledgeChecks
                ->every(fn ($check) => $check->passedBy($user));

            // Out of attempts on a check they have not passed is as terminal as
            // failing the final exam, and should not read as "in progress".
            $checkExhausted = $knowledgeChecks->contains(
                fn ($check) => ! $check->passedBy($user) && ! $check->hasAttemptsRemainingFor($user)
            );

            // ------------------------------------------- practical tasks
            /*
             * Every published practical on the course has to be passed.
             *
             * This is the difference between "read the material" and "can do the
             * job". Without it a trainee could finish a course, collect a
             * certificate and be awarded a competency level having never
             * demonstrated anything — which is precisely the model this platform
             * is moving away from.
             */
            $practicals = $course->practicalTasks()->where('is_published', true)->get();

            $practicalsPassed = $practicals->isEmpty()
                ? 0
                : $course->practicalTasks()
                    ->where('is_published', true)
                    ->whereHas('submissions', fn ($q) => $q
                        ->where('user_id', $user->id)
                        ->where('passed', true))
                    ->count();

            $practicalsSatisfied = $practicals->count() === $practicalsPassed;

            // ------------------------------------------------------- status
            $lessonsSatisfied = $totalLessons > 0 && $progress->completed_topics >= $totalLessons;
            $hasStarted = $progress->completed_topics > 0 || $progress->quiz_attempts_count > 0;

            $progress->started_at ??= $hasStarted ? now() : null;
            $progress->last_activity_at = now();

            if ($lessonsSatisfied && $quizSatisfied && $practicalsSatisfied && $checksSatisfied) {
                $progress->status = ProgressStatus::Completed;
                $progress->completed_at ??= now();
            } else {
                $progress->completed_at = null;

                $progress->status = match (true) {
                    // Out of attempts on a final assessment they have not passed.
                    $finalQuiz !== null
                        && ! $quizSatisfied
                        && ! $finalQuiz->hasAttemptsRemainingFor($user) => ProgressStatus::Failed,

                    // Same for a knowledge check they can no longer retake:
                    // the course can never be finished, so it must not sit at
                    // "in progress" waiting for something that cannot happen.
                    $checkExhausted => ProgressStatus::Failed,

                    $this->isOverdue($user, $course) => ProgressStatus::Overdue,

                    $hasStarted => ProgressStatus::InProgress,

                    default => ProgressStatus::NotStarted,
                };
            }

            $progress->save();

            // Certificate issuance hangs off completion rather than off the
            // controller, so every path that can finish a course — a topic tick,
            // a passing quiz, an admin backfill — issues one.
            if ($progress->status === ProgressStatus::Completed) {
                $this->issueCertificate->handle($user, $course, $progress);

                // A course rarely earns a level on its own — the action checks
                // whether this completion was the one a rung was still waiting
                // for. Idempotent, so running it on every completion is cheaper
                // than tracking which course is the last of a set.
                $this->awardCompetencyLevel->forCourse($user, $course);
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
