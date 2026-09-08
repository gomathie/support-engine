<?php

namespace App\Actions\Competency;

use App\Enums\RefresherStatus;
use App\Models\Refresher;
use App\Models\TraineeLevel;
use Illuminate\Support\Collection;

/**
 * Puts the 30- and 90-day refreshers in the diary when a level is awarded.
 *
 * Called from `AwardCompetencyLevel`, which runs on every progress
 * recalculation — so this has to be idempotent, and it is: the unique index on
 * (trainee_level_id, interval_days) plus `firstOrCreate` means running it a
 * thousand times leaves two rows.
 *
 * The baseline is captured here rather than looked up later. See the migration
 * for why that matters.
 */
class ScheduleRefreshers
{
    /**
     * @return Collection<int, Refresher> the refreshers now in the diary
     */
    public function handle(TraineeLevel $award): Collection
    {
        // A revoked award is not refreshed. There is nothing to check the
        // retention of, and asking somebody to sit a refresher for a level
        // that was taken away would be worse than saying nothing.
        if (! $award->isActive()) {
            return collect();
        }

        $baseline = $this->baseline($award);
        $awardedAt = $award->awarded_at ?? $award->created_at ?? now();

        return collect(Refresher::INTERVALS)->map(
            fn (int $days) => Refresher::query()->firstOrCreate(
                [
                    'trainee_level_id' => $award->getKey(),
                    'interval_days' => $days,
                ],
                [
                    'user_id' => $award->user_id,
                    'due_at' => $awardedAt->copy()->addDays($days),
                    'baseline_score' => $baseline,
                    'status' => RefresherStatus::Scheduled,
                ],
            )
        );
    }

    /**
     * The score of the sitting that earned the level.
     *
     * Null for a hand-granted award — a trainer granting a level directly
     * leaves no exam score behind it. The refresher still happens; it simply
     * has nothing to be a percentage of, and `retention` stays null rather
     * than being reported as a fall from zero.
     */
    private function baseline(TraineeLevel $award): ?float
    {
        $score = $award->quizAttempt?->score;

        return $score === null ? null : (float) $score;
    }
}
