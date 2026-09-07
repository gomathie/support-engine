<?php

namespace App\Enums;

/**
 * Where a practical submission sits between "started" and "marked".
 *
 * `Returned` is distinct from `Graded` with a fail: it means a grader sent the
 * work back for another go without recording a fail against it. Collapsing the
 * two would make "how many people failed this task" unanswerable.
 */
enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Graded = 'graded';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Awaiting marking',
            self::Graded => 'Marked',
            self::Returned => 'Returned for revision',
        };
    }

    /** Whether the trainee can still edit it. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }

    public function awaitsMarking(): bool
    {
        return $this === self::Submitted;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
