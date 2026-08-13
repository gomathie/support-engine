<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Administrators bypass the rest of this class. Returning null (rather than
     * false) for everyone else lets the individual methods decide.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * An employee may open a course only if it is enrolled to them *and*
     * published. Drafts are invisible to everyone but authors, so a guessed
     * slug does not expose unfinished material.
     */
    public function view(User $user, Course $course): bool
    {
        if (! $course->isVisibleToEmployees()) {
            return false;
        }

        if ($user->hasRole(Role::Manager->value)) {
            return true;
        }

        return $course->enrollments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('courses.create');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasPermissionTo('courses.update');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasPermissionTo('courses.delete');
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->hasPermissionTo('courses.publish');
    }

    /**
     * Self-enrollment is only possible on published, optional courses. Required
     * training is assigned, never opted into.
     */
    public function enrollSelf(User $user, Course $course): bool
    {
        if (! $course->isVisibleToEmployees() || $course->is_required) {
            return false;
        }

        return ! $course->enrollments()->where('user_id', $user->id)->exists();
    }

    /** Managers can assign courses, but only to people in departments they run. */
    public function assign(User $user, Course $course): bool
    {
        return $user->hasRole(Role::Manager->value);
    }
}
