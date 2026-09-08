<?php

namespace App\Policies;

use App\Models\Refresher;
use App\Models\User;

/**
 * A refresher belongs to one person and nobody else sits it.
 *
 * Note there is no `before()` admin bypass here, and that is deliberate.
 * Everywhere else in this application an admin can see anything; here, "seeing"
 * means opening somebody's paper and being able to submit answers as them. An
 * admin who wants to know how a trainee did reads the metric, not the paper.
 */
class RefresherPolicy
{
    /** Reading the outcome. The owner, and anyone who may see their results. */
    public function view(User $user, Refresher $refresher): bool
    {
        return $user->getKey() === $refresher->user_id
            || $user->can('reports.view-all-departments');
    }

    /**
     * Sitting it. The owner alone, while it is open.
     *
     * `isOpen()` carries both halves: due, and not past its window. A refresher
     * sat six months late measures nothing anybody wanted measured.
     */
    public function attempt(User $user, Refresher $refresher): bool
    {
        return $user->getKey() === $refresher->user_id && $refresher->isOpen();
    }
}
