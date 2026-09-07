<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PracticalTask;
use App\Models\User;

/**
 * Access to a practical task, derived from access to its course.
 *
 * Same rule as TopicPolicy: never granted directly, or an unenrolled employee
 * could read the brief by URL even though the course listing hides it.
 */
class PracticalTaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Attempting is excluded: browsing a task as an administrator must not
        // create submissions against courses nobody assigned them.
        if ($ability === 'attempt') {
            return null;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function view(User $user, PracticalTask $task): bool
    {
        if (! $task->is_published) {
            return false;
        }

        return $user->can('view', $task->course);
    }

    /**
     * Starting or handing in work on it.
     *
     * Enrolment, not merely visibility — trainers and admins browse content
     * without it counting as their own training, exactly as topic completion
     * works.
     */
    public function attempt(User $user, PracticalTask $task): bool
    {
        if (! $this->view($user, $task)) {
            return false;
        }

        return $task->course->enrollments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('topics.manage');
    }

    public function update(User $user, PracticalTask $task): bool
    {
        return $user->hasPermissionTo('topics.manage');
    }

    public function delete(User $user, PracticalTask $task): bool
    {
        return $user->hasPermissionTo('topics.manage');
    }
}
