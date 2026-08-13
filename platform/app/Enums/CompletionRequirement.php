<?php

namespace App\Enums;

enum CompletionRequirement: string
{
    /** Opening the lesson marks it done. */
    case View = 'view';

    /** The employee has to tick it off — the prototype's checklist behaviour. */
    case Acknowledge = 'acknowledge';

    /** The attached quiz has to be passed. */
    case Quiz = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::View => 'Viewing it is enough',
            self::Acknowledge => 'Employee marks it complete',
            self::Quiz => 'Attached quiz must be passed',
        };
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
