<?php

namespace App\Console\Commands;

use App\Enums\RefresherStatus;
use App\Models\Refresher;
use Illuminate\Console\Command;

/**
 * Closes refreshers nobody sat inside their window (PA-18).
 *
 * Without this they sit as `scheduled` for ever, which quietly corrupts two
 * things: the dashboard would keep offering a refresher that is months stale,
 * and KPI 6 could never report how many were missed — so a retention figure
 * computed from a third of the cohort would look exactly like one computed
 * from all of it.
 *
 * **Missed is not a fail.** It is not scored, and it is deliberately kept out
 * of the retention average: a refresher nobody sat measures the process, not
 * the person's knowledge. Counting it as zero would report a collapse that
 * has not been observed.
 */
class CloseLapsedRefreshers extends Command
{
    protected $signature = 'training:close-lapsed-refreshers';

    protected $description = 'Mark refreshers that were never sat inside their window as missed';

    public function handle(): int
    {
        $cutoff = now()->subDays(Refresher::WINDOW_DAYS);

        $lapsed = Refresher::query()
            ->where('status', RefresherStatus::Scheduled->value)
            ->where('due_at', '<=', $cutoff)
            ->update([
                'status' => RefresherStatus::Missed->value,
                'updated_at' => now(),
            ]);

        $this->info($lapsed === 0
            ? 'No refreshers have lapsed.'
            : $lapsed.' refresher(s) marked as missed.');

        return self::SUCCESS;
    }
}
