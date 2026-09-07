<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PracticalSubmission;
use App\Models\User;

/**
 * Who may see and mark a practical submission.
 *
 * The same split PA-4 established for quiz attempts: reading a transcript and
 * putting a mark on it are different rights. A trainer may read any submission
 * with `transcripts.view-all`; marking stays inside their own cohort.
 */
class PracticalSubmissionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        /*
         * Excluded from the administrator bypass, for the same reason as quiz
         * grading: nobody marks their own paper, and nobody hands in work on
         * somebody else's behalf.
         */
        if (in_array($ability, ['grade', 'return', 'submit', 'update'], true)) {
            return null;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Trainer->value);
    }

    public function view(User $user, PracticalSubmission $submission): bool
    {
        if ($submission->user_id === $user->id) {
            return true;
        }

        if (! $user->hasRole(Role::Trainer->value)) {
            return false;
        }

        return $user->hasPermissionTo('transcripts.view-all')
            || $user->canGrade($submission->user);
    }

    /** Editing the write-up. Only the author, and only while it is open. */
    public function update(User $user, PracticalSubmission $submission): bool
    {
        return $submission->user_id === $user->id && $submission->isEditable();
    }

    public function submit(User $user, PracticalSubmission $submission): bool
    {
        return $submission->user_id === $user->id && $submission->isEditable();
    }

    /**
     * Marking it. Cohort-scoped, and never your own work.
     *
     * A submission that has already settled is not re-marked through this path —
     * a grader revising their own marks updates their existing grading, which
     * `hasEnoughGradings()` still counts as one opinion.
     */
    public function grade(User $user, PracticalSubmission $submission): bool
    {
        return $user->canGrade($submission->user);
    }

    public function return(User $user, PracticalSubmission $submission): bool
    {
        return $user->canGrade($submission->user);
    }

    public function delete(User $user, PracticalSubmission $submission): bool
    {
        return false;
    }
}
