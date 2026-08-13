<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentSource;
use App\Models\AssignmentRule;
use App\Models\User;

/**
 * Turns assignment rules into enrollments.
 *
 * Nothing here is hard-coded: "Operations gets Fleet Safety Training" is a row
 * in assignment_rules, not a branch in this class. Run it after a rule changes,
 * after somebody moves department, and on a schedule to catch new starters.
 */
class SyncAssignmentRules
{
    public function __construct(
        private readonly EnrollEmployee $enroll,
    ) {}

    /** @return int Number of enrollments created. */
    public function all(): int
    {
        $created = 0;

        AssignmentRule::query()
            ->active()
            ->with('course')
            ->each(function (AssignmentRule $rule) use (&$created): void {
                $created += $this->forRule($rule);
            });

        return $created;
    }

    public function forRule(AssignmentRule $rule): int
    {
        if (! $rule->is_active || ! $rule->course) {
            return 0;
        }

        $created = 0;

        // Chunked: a rule targeting "all" on a large org would otherwise load
        // every employee into memory at once.
        $rule->matchingUsers()->chunkById(200, function ($users) use ($rule, &$created): void {
            foreach ($users as $user) {
                $existed = $user->enrollments()
                    ->where('course_id', $rule->course_id)
                    ->exists();

                $this->enroll->handle(
                    user: $user,
                    course: $rule->course,
                    source: EnrollmentSource::Rule,
                    rule: $rule,
                );

                if (! $existed) {
                    $created++;
                }
            }
        });

        return $created;
    }

    /**
     * Re-evaluates every rule for one person. Called when somebody joins, or
     * moves between departments — they pick up their new department's training
     * without an administrator remembering to assign it.
     */
    public function forUser(User $user): int
    {
        $created = 0;

        AssignmentRule::query()
            ->active()
            ->with('course')
            ->each(function (AssignmentRule $rule) use ($user, &$created): void {
                if (! $rule->course) {
                    return;
                }

                $matches = $rule->matchingUsers()->whereKey($user->id)->exists();

                if (! $matches) {
                    return;
                }

                $existed = $user->enrollments()->where('course_id', $rule->course_id)->exists();

                $this->enroll->handle(
                    user: $user,
                    course: $rule->course,
                    source: EnrollmentSource::Rule,
                    rule: $rule,
                );

                if (! $existed) {
                    $created++;
                }
            });

        return $created;
    }
}
