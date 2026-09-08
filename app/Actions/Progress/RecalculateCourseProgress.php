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

            // --------------------------------------------------- final exams
            /*
             * A course may have several, and every one of them must be passed.
             *
             * The PILOT examination is three papers — A, B and C — so a single
             * final exam was never the right shape. This used to read
             * `finalQuiz()->first()`, which meant a second course-scoped quiz
             * was sittable and counted for nothing: the official 40-question
             * Section A sat in front of trainees deciding precisely nothing
             * until somebody went looking.
             */
            $finalExams = $course->finalQuiz()->where('is_published', true)->get();

            $quizSatisfied = $finalExams->every(fn ($exam) => $exam->passedBy($user));

            if ($finalExams->isNotEmpty()) {
                /*
                 * One number for the certificate and the report: the mean of
                 * the best attempt on each paper. Unsat papers count as null
                 * rather than zero, so a part-finished examination does not
                 * report a score that looks like a failure.
                 */
                $scores = $finalExams
                    ->map(fn ($exam) => $exam->bestAttemptFor($user)?->score)
                    ->filter(fn ($score) => $score !== null);

                $progress->final_score = $scores->isEmpty()
                    ? null
                    : round($scores->avg(), 2);

                $progress->quiz_attempts_count = $finalExams
                    ->sum(fn ($exam) => $exam->attemptsUsedBy($user));
            }

            // Out of attempts on *any* paper is terminal, not just the first.
            $finalExhausted = $finalExams->contains(
                fn ($exam) => ! $exam->passedBy($user) && ! $exam->hasAttemptsRemainingFor($user)
            );

            // ------------------------------------------ knowledge checks
            /*
             * Every lesson ends with a knowledge check, and every one of them
             * has to be passed.
             *
             * These are module-scoped quizzes. Without this they were
             * decoration: a trainee could skip every check and still finish the
             * course on the final exam alone, which makes "you must pass" untrue
             * for the thing sitting at the end of each lesson.
             */
            $knowledgeChecks = $course->quizzes()
                ->whereNotNull('module_id')
                ->whereNull('lesson_id')
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
            $lessonsSatisfied = $totalLessons > 0 && $progress->completed_lessons >= $totalLessons;
            $hasStarted = $progress->completed_lessons > 0 || $progress->quiz_attempts_count > 0;

            $progress->started_at ??= $hasStarted ? now() : null;
            $progress->last_activity_at = now();

            if ($lessonsSatisfied && $quizSatisfied && $practicalsSatisfied && $checksSatisfied) {
                $progress->status = ProgressStatus::Completed;
                $progress->completed_at ??= now();
            } else {
                $progress->completed_at = null;

                $progress->status = match (true) {
                    // Out of attempts on any final paper they have not passed.
                    $finalExhausted => ProgressStatus::Failed,

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
            // controller, so every path that can finish a course — a lesson tick,
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
