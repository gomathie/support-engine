<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Manager->value);
    }

    /**
     * Employee data privacy. A manager sees the people in the departments they
     * run and nobody else; an employee sees only themselves.
     */
    public function view(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return true;
        }

        if ($user->hasRole(Role::Manager->value)) {
            return in_array($target->department_id, $user->visibleDepartmentIds(), true);
        }

        return false;
    }

    /** Reading somebody's training record — progress, scores, attempts. */
    public function viewProgress(User $user, User $target): bool
    {
        return $this->view($user, $target);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $target): bool
    {
        return $user->is($target);
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    /** Nobody may hand out a role they do not hold themselves. */
    public function assignRole(User $user, User $target): bool
    {
        return false;
    }
}
