<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\QuizAttempt;
use App\Models\User;

/**
 * Every ability on an attempt, for both surfaces — the employee's results
 * screen and the examiner's grading queue.
 *
 * Laravel resolves a policy by model name, so this class owns QuizAttempt
 * outright. Abilities defined on QuizPolicy would never be consulted for an
 * attempt, however sensibly they were named.
 */
class QuizAttemptPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        /*
         * Two abilities are excluded from the administrator bypass:
         *
         *  - grade, so "never mark your own paper" holds for everyone;
         *  - submit, so nobody can submit an attempt that is not theirs, or one
         *    that has already been graded.
         */
        if (in_array($ability, ['grade', 'submit'], true)) {
            return null;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    /** Listing attempts in the admin panel. */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Trainer->value);
    }

    /**
     * Reading an attempt — its answers, its score.
     *
     * A trainee sees their own. A trainer with `transcripts.view-all` may read
     * anybody's, which is deliberate: a trainer covering a colleague, or
     * checking how a topic lands across the intake, needs the whole picture.
     * Reading is not grading — see grade() below.
     */
    public function view(User $user, QuizAttempt $attempt): bool
    {
        if ($attempt->user_id === $user->id) {
            return true;
        }

        if (! $user->hasRole(Role::Trainer->value)) {
            return false;
        }

        return $user->hasPermissionTo('transcripts.view-all')
            || $user->canGrade($attempt->user);
    }

    public function submit(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id && $attempt->isInProgress();
    }

    /**
     * Marking written answers.
     *
     * Restricted to the trainer's own cohort, unlike view(). A trainer may read
     * every transcript but may only put a mark on the trainees they are
     * responsible for — and nobody marks their own paper, whatever their role.
     */
    public function grade(User $user, QuizAttempt $attempt): bool
    {
        return $user->canGrade($attempt->user);
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
