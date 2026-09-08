<?php

namespace App\Actions\Competency;

use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Level;
use App\Models\LevelRequirement;
use App\Models\TraineeLevel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Decides whether somebody has earned a level, and records it if so.
 *
 * A level is not awarded by finishing one course — it is awarded when every
 * course the (level, area) pair requires is complete. Called after any course
 * completes, so the award happens on the same event that issues a certificate.
 *
 * Idempotent throughout: it runs on every progress recalculation, which happens
 * on every lesson tick.
 */
class AwardCompetencyLevel
{
    public function __construct(
        private readonly ScheduleRefreshers $refreshers,
    ) {}

    /**
     * Evaluate every (level, area) pair this course contributes to.
     *
     * @return Collection<int, TraineeLevel> newly granted awards
     */
    public function forCourse(User $user, Course $course): Collection
    {
        $pairs = LevelRequirement::query()
            ->where('course_id', $course->getKey())
            ->get(['level_id', 'competency_area_id'])
            ->unique(fn ($r) => $r->level_id.':'.$r->competency_area_id);

        return $pairs
            ->map(fn ($pair) => $this->evaluate($user, (int) $pair->level_id, (int) $pair->competency_area_id))
            ->filter()
            ->values();
    }

    /**
     * Award the pair if every requirement is met, the rung below is held, and
     * it is not already granted. Returns null when nothing changed.
     */
    public function evaluate(User $user, int $levelId, int $areaId): ?TraineeLevel
    {
        return DB::transaction(function () use ($user, $levelId, $areaId): ?TraineeLevel {
            $existing = TraineeLevel::query()
                ->where('user_id', $user->getKey())
                ->where('level_id', $levelId)
                ->where('competency_area_id', $areaId)
                ->lockForUpdate()
                ->first();

            // Already held. A revoked award is left revoked — restoring it is a
            // deliberate act, not something a lesson tick should do silently.
            if ($existing) {
                return null;
            }

            if (! $this->requirementsMet($user, $levelId, $areaId)) {
                return null;
            }

            if (! $this->lowerRungHeld($user, $levelId, $areaId)) {
                return null;
            }

            $award = TraineeLevel::query()->create([
                'user_id' => $user->getKey(),
                'level_id' => $levelId,
                'competency_area_id' => $areaId,
                'awarded_at' => now(),

                // Null — the system awarded this, not a person.
                'awarded_by' => null,

                'quiz_attempt_id' => $this->evidenceAttemptId($user, $levelId, $areaId),
            ]);

            /*
             * The 30- and 90-day refreshers go in the diary now, while the exam
             * score that earned the level is still to hand (PA-18).
             *
             * Scheduling them here rather than on a nightly sweep means the
             * baseline is captured at the moment of the award, and a refresher
             * cannot be missed because a job did not run.
             */
            $this->refreshers->handle($award);

            return $award;
        });
    }

    /** Every course the pair requires must be complete for this user. */
    private function requirementsMet(User $user, int $levelId, int $areaId): bool
    {
        $required = LevelRequirement::query()
            ->where('level_id', $levelId)
            ->where('competency_area_id', $areaId)
            ->pluck('course_id');

        // A pair with no requirements is not an automatic pass — it is an
        // unconfigured level, and awarding it would be meaningless.
        if ($required->isEmpty()) {
            return false;
        }

        $completed = CourseProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('course_id', $required)
            ->where('status', ProgressStatus::Completed->value)
            ->count();

        return $completed === $required->count();
    }

    /**
     * The ladder is ordered: Second is not granted to somebody who does not
     * hold Basic in the same area. Prevents a mis-assigned advanced course
     * from vaulting a trainee past the foundation.
     */
    private function lowerRungHeld(User $user, int $levelId, int $areaId): bool
    {
        $level = Level::query()->find($levelId);

        if (! $level) {
            return false;
        }

        $below = $level->previous();

        if (! $below) {
            return true;
        }

        // Only enforced where the lower rung is actually configured for this
        // area — otherwise an area that starts at Second could never be earned.
        $lowerIsConfigured = LevelRequirement::query()
            ->where('level_id', $below->getKey())
            ->where('competency_area_id', $areaId)
            ->exists();

        if (! $lowerIsConfigured) {
            return true;
        }

        return TraineeLevel::query()
            ->active()
            ->where('user_id', $user->getKey())
            ->where('level_id', $below->getKey())
            ->where('competency_area_id', $areaId)
            ->exists();
    }

    /**
     * The most recent passing attempt across the pair's courses — the sitting
     * that stands as evidence for the award.
     */
    private function evidenceAttemptId(User $user, int $levelId, int $areaId): ?int
    {
        $courseIds = LevelRequirement::query()
            ->where('level_id', $levelId)
            ->where('competency_area_id', $areaId)
            ->pluck('course_id');

        return $user->quizAttempts()
            ->whereIn('course_id', $courseIds)
            ->where('passed', true)
            ->latest('completed_at')
            ->value('id');
    }
}
