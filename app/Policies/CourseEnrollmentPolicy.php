<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CourseEnrollment;
use App\Models\User;

class CourseEnrollmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function view(User $user, CourseEnrollment $enrollment): bool
    {
        if ($enrollment->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole(Role::Manager->value)) {
            return in_array(
                $enrollment->user->department_id,
                $user->visibleDepartmentIds(),
                true,
            );
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Manager->value);
    }

    public function delete(User $user, CourseEnrollment $enrollment): bool
    {
        return $user->hasRole(Role::Manager->value)
            && in_array($enrollment->user->department_id, $user->visibleDepartmentIds(), true);
    }

    /**
     * Wiping your own progress. Allowed — the prototype had a "reset all
     * progress" button and it is genuinely useful when re-taking training — but
     * only ever on your own record, and only when the course is not already
     * complete, so a certificate cannot be orphaned.
     */
    public function reset(User $user, CourseEnrollment $enrollment): bool
    {
        if ($enrollment->user_id !== $user->id) {
            return false;
        }

        return ! $user->certificates()
            ->where('course_id', $enrollment->course_id)
            ->exists();
    }
}
