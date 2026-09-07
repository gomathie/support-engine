<?php

namespace App\Actions\Cohorts;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single place a trainee's trainer changes.
 *
 * Every path — first assignment, reassignment, unassignment — closes the old
 * row rather than deleting it and writes a log entry. Assignments are the
 * provenance of a grade, so they are append-only history, not mutable state.
 */
class AssignTrainee
{
    /**
     * Assign or reassign. Idempotent: assigning to the trainer who already
     * holds the trainee is a no-op rather than a duplicate row, so a
     * double-submitted form does not spawn history.
     */
    public function handle(
        User $trainee,
        User $trainer,
        User $actor,
        ?string $reason = null,
    ): void {
        if ($trainee->is($trainer)) {
            throw ValidationException::withMessages([
                'trainer' => 'Somebody cannot be their own trainer.',
            ]);
        }

        if (! $trainer->isTrainer() && ! $trainer->isAdmin()) {
            throw ValidationException::withMessages([
                'trainer' => 'That person is not a trainer.',
            ]);
        }

        DB::transaction(function () use ($trainee, $trainer, $actor, $reason): void {
            // Lock the trainee's open assignment. Without this, two admins
            // reassigning at once both see "no current trainer" and the partial
            // unique index rejects the second write as a raw database error.
            $current = DB::table('trainer_trainee')
                ->where('trainee_id', $trainee->getKey())
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($current && (int) $current->trainer_id === (int) $trainer->getKey()) {
                return;
            }

            $inherited = 0;

            if ($current) {
                DB::table('trainer_trainee')
                    ->where('id', $current->id)
                    ->update(['ended_at' => now(), 'updated_at' => now()]);

                $inherited = $this->pendingGradingCount($trainee);
            }

            DB::table('trainer_trainee')->insert([
                'trainer_id' => $trainer->getKey(),
                'trainee_id' => $trainee->getKey(),
                'assigned_by' => $actor->getKey(),
                'assigned_at' => now(),
                'ended_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('trainer_assignment_logs')->insert([
                'trainee_id' => $trainee->getKey(),
                'from_trainer_id' => $current->trainer_id ?? null,
                'to_trainer_id' => $trainer->getKey(),
                'actor_id' => $actor->getKey(),
                'action' => $current ? 'reassigned' : 'assigned',
                'reason' => $reason,

                // Nothing moves physically — the new trainer inherits the queue
                // by virtue of holding the cohort. This records how much that
                // was, because it cannot be reconstructed later.
                'pending_grading_inherited' => $inherited,

                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /** Removes a trainee from their trainer without assigning a new one. */
    public function unassign(User $trainee, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($trainee, $actor, $reason): void {
            $current = DB::table('trainer_trainee')
                ->where('trainee_id', $trainee->getKey())
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if (! $current) {
                return;
            }

            DB::table('trainer_trainee')
                ->where('id', $current->id)
                ->update(['ended_at' => now(), 'updated_at' => now()]);

            DB::table('trainer_assignment_logs')->insert([
                'trainee_id' => $trainee->getKey(),
                'from_trainer_id' => $current->trainer_id,
                'to_trainer_id' => null,
                'actor_id' => $actor->getKey(),
                'action' => 'unassigned',
                'reason' => $reason,
                'pending_grading_inherited' => $this->pendingGradingCount($trainee),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Written answers on this trainee's attempts that nobody has marked yet —
     * the work a new trainer picks up.
     */
    private function pendingGradingCount(User $trainee): int
    {
        return DB::table('quiz_answers')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_answers.quiz_attempt_id')
            ->where('quiz_attempts.user_id', $trainee->getKey())
            ->whereNull('quiz_answers.graded_at')
            ->count();
    }
}
