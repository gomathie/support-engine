<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    /**
     * Access to a lesson is derived from access to its course, never granted
     * directly — otherwise an unenrolled employee could reach lesson content by
     * URL even though the course listing hides it.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        if (! $lesson->is_published) {
            return false;
        }

        return $user->can('view', $lesson->course);
    }

    public function complete(User $user, Lesson $lesson): bool
    {
        if (! $this->view($user, $lesson)) {
            return false;
        }

        // Managers and admins browse content without it counting as training.
        // Progress rows exist for people the course is actually assigned to.
        return $lesson->course->enrollments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('lessons.manage');
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->hasPermissionTo('lessons.manage');
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->hasPermissionTo('lessons.manage');
    }
}
