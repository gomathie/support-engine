<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Manual = 'manual';
    case Department = 'department';
    case RoleBased = 'role';
    case Rule = 'rule';
    case Self = 'self';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Assigned by an administrator',
            self::Department => 'Department assignment',
            self::RoleBased => 'Role assignment',
            self::Rule => 'Automatic rule',
            self::Self => 'Self-enrolled',
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
