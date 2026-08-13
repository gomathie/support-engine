<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\SupportCase;
use App\Models\User;

class SupportCasePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_active ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A case note carries a named customer and often a driver's vehicle. It is
     * the working notes of one support agent, not shared team data — so not even
     * an administrator reads it through this route.
     */
    public function view(User $user, SupportCase $case): bool
    {
        return $case->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SupportCase $case): bool
    {
        return $case->user_id === $user->id;
    }

    public function delete(User $user, SupportCase $case): bool
    {
        return $case->user_id === $user->id || $user->hasRole(Role::Admin->value);
    }
}
