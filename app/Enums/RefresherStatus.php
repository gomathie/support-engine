<?php

namespace App\Enums;

/**
 * Where a refresher is in its short life.
 *
 * There is no "in progress". A refresher is five auto-marked questions and is
 * meant to take three minutes; a trainee who opens one and wanders off has a
 * `started_at` and is still `Scheduled`, which is the honest description.
 */
enum RefresherStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';

    /**
     * Past its window and never sat.
     *
     * Missed is a finding rather than a failure: it says the refresher did not
     * happen, which is information about the process, not about the trainee's
     * retention. It is deliberately **not** scored as zero — averaging a
     * not-taken refresher into KPI 6 as nought would report a collapse in
     * retention that nobody has measured.
     */
    case Missed = 'missed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Due',
            self::Completed => 'Completed',
            self::Missed => 'Missed',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
