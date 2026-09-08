<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\LessonResource;
use App\Models\User;

class LessonResourcePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    /**
     * The only gate on an internal document. Resources live on a private disk
     * with no public URL, so if this returns true the bytes are served and if it
     * returns false they are not — there is no other path to the file.
     */
    public function download(User $user, LessonResource $resource): bool
    {
        if (! $resource->is_downloadable) {
            return false;
        }

        return $user->can('view', $resource->lesson);
    }

    /** Inline rendering (an image in the page, a PDF in the viewer). */
    public function stream(User $user, LessonResource $resource): bool
    {
        return $user->can('view', $resource->lesson);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('lessons.manage');
    }

    public function update(User $user, LessonResource $resource): bool
    {
        return $user->hasPermissionTo('lessons.manage');
    }

    public function delete(User $user, LessonResource $resource): bool
    {
        return $user->hasPermissionTo('lessons.manage');
    }
}
