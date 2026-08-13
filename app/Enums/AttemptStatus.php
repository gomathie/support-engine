<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';

    /**
     * Submitted, objective questions marked, waiting on an examiner for the
     * written ones. No score is shown to the employee yet — a partial score
     * would read as a final one.
     */
    case PendingReview = 'pending_review';

    case Completed = 'completed';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::PendingReview => 'Awaiting review',
            self::Completed => 'Completed',
            self::Abandoned => 'Abandoned',
        };
    }

    /** Whether the attempt has been submitted, whatever happens next. */
    public function isSubmitted(): bool
    {
        return $this === self::PendingReview || $this === self::Completed;
    }
}
