<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\QuizAttempt;
use App\Models\User;

/**
 * Who may see and mark an attempt in the admin panel.
 *
 * Distinct from QuizPolicy::viewAttempt(), which governs the employee-facing
 * results screen. Both agree on the same rule — your own, or your department's
 * if you manage it — but they guard different surfaces.
 */
class QuizAttemptPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Grading is excluded from the administrator bypass on purpose, so the
        // "never mark your own paper" rule below holds for everyone.
        if ($ability === 'grade') {
            return null;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Manager->value);
    }

    public function view(User $user, QuizAttempt $attempt): bool
    {
        if (! $user->hasRole(Role::Manager->value)) {
            return false;
        }

        return in_array(
            $attempt->user->department_id,
            $user->visibleDepartmentIds(),
            true,
        );
    }

    /**
     * Marking is the same permission as viewing, plus the requirement that the
     * attempt is actually gradable. An employee can never mark anything —
     * including, especially, their own paper.
     */
    public function grade(User $user, QuizAttempt $attempt): bool
    {
        if ($attempt->user_id === $user->id) {
            return false;
        }

        if ($user->hasRole(Role::Admin->value)) {
            return true;
        }

        return $this->view($user, $attempt);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, QuizAttempt $attempt): bool
    {
        return false;
    }

    public function delete(User $user, QuizAttempt $attempt): bool
    {
        return false;
    }
}
