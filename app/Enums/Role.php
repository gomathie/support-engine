<?php

namespace App\Enums;

/**
 * The three roles in the competency model.
 *
 * Renamed from admin/manager/employee in Phase 0. The old names described an
 * org chart; these describe what someone does on the training portal, which is
 * what the policies actually branch on. A trainer is not necessarily anybody's
 * line manager, and a trainee is not a job title.
 *
 * @see database/migrations/*_rename_roles_for_competency_model.php
 */
enum Role: string
{
    case Admin = 'admin';
    case Trainer = 'trainer';
    case Trainee = 'trainee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Trainer => 'Trainer',
            self::Trainee => 'Trainee',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full control, including assigning trainees to trainers.',
            self::Trainer => 'Builds content and grades their assigned cohort.',
            self::Trainee => 'Works through assigned training and sits assessments.',
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
