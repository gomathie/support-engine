<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    /**
     * Access to a topic is derived from access to its course, never granted
     * directly — otherwise an unenrolled employee could reach topic content by
     * URL even though the course listing hides it.
     */
    public function view(User $user, Topic $topic): bool
    {
        if (! $topic->is_published) {
            return false;
        }

        return $user->can('view', $topic->course);
    }

    public function complete(User $user, Topic $topic): bool
    {
        if (! $this->view($user, $topic)) {
            return false;
        }

        // Managers and admins browse content without it counting as training.
        // Progress rows exist for people the course is actually assigned to.
        return $topic->course->enrollments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('topics.manage');
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('topics.manage');
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('topics.manage');
    }
}
