<?php

namespace App\Enums;

enum ProgressStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Overdue => 'Overdue',
        };
    }

    /**
     * Maps onto the prototype's design tokens so the employee UI keeps the same
     * colour language it had as static HTML.
     */
    public function tone(): string
    {
        return match ($this) {
            self::NotStarted => 'neutral',
            self::InProgress => 'primary',
            self::Completed => 'positive',
            self::Failed, self::Overdue => 'negative',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed;
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
