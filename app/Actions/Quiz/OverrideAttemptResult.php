<?php

namespace App\Actions\Quiz;

use App\Actions\Progress\RecalculateCourseProgress;
use App\Models\GradeOverrideLog;
use App\Models\QuizAttempt;
use App\Models\TraineeLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Overturns a marked pass or fail, with a reason on the record (PA-12).
 *
 * Promotion is automatic; this is the exception path. Two cases justify it: a
 * pass secured on a question later found defective, and a fail a Trainer wants
 * to overturn on appeal.
 *
 * Three things this deliberately does NOT do:
 *
 *  - It does not touch the score. "Scored 62% but passed on appeal" is the
 *    truth; rewriting the score to 70% would erase the appeal.
 *  - It does not write straight into `passed` and forget. The override is
 *    stored, so re-grading a written answer cannot silently undo it.
 *  - It does not revoke levels indiscriminately. Only awards whose *evidence*
 *    was this very attempt are withdrawn, and only when a pass becomes a fail.
 */
class OverrideAttemptResult
{
    public function __construct(
        private readonly RecalculateCourseProgress $recalculate,
    ) {}

    public function handle(
        QuizAttempt $attempt,
        User $actor,
        bool $passed,
        string $reason,
    ): QuizAttempt {
        return DB::transaction(function () use ($attempt, $actor, $passed, $reason): QuizAttempt {
            $attempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());

            $wasPassed = (bool) $attempt->passed;

            $attempt->forceFill([
                'override_passed' => $passed,
                'override_reason' => $reason,
                'overridden_by' => $actor->getKey(),
                'overridden_at' => now(),

                // The effective verdict, so every reader downstream — progress,
                // certificates, the competency ladder — keeps using one column.
                'passed' => $passed,
            ])->save();

            $revoked = 0;

            // A pass turned into a fail cannot leave behind a level that this
            // attempt was the evidence for.
            if ($wasPassed && ! $passed) {
                $revoked = $this->revokeLevelsEvidencedBy($attempt, $actor, $reason);
            }

            GradeOverrideLog::query()->create([
                'quiz_attempt_id' => $attempt->getKey(),
                'trainee_id' => $attempt->user_id,
                'actor_id' => $actor->getKey(),
                'action' => GradeOverrideLog::ACTION_OVERRIDDEN,
                'from_passed' => $wasPassed,
                'to_passed' => $passed,
                'score_at_override' => $attempt->score,
                'reason' => $reason,
                'levels_revoked' => $revoked,
                'occurred_at' => now(),
            ]);

            $attempt->refresh();

            // Completion, certificates and any level this now qualifies for.
            $this->recalculate->handle($attempt->user, $attempt->course);

            return $attempt->refresh();
        });
    }

    /**
     * Withdraw an override and let the marked result stand again.
     *
     * The reason is still required: reverting is as consequential as the
     * original override, and equally worth explaining.
     */
    public function withdraw(QuizAttempt $attempt, User $actor, string $reason): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $actor, $reason): QuizAttempt {
            $attempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());

            if ($attempt->override_passed === null) {
                return $attempt;
            }

            $wasPassed = (bool) $attempt->passed;

            // What the paper actually scored, against the pass mark in force
            // when it was sat.
            $passMark = $attempt->passing_score ?? $attempt->quiz->passing_score;
            $marked = (float) $attempt->score >= $passMark;

            $attempt->forceFill([
                'override_passed' => null,
                'override_reason' => null,
                'overridden_by' => null,
                'overridden_at' => null,
                'passed' => $marked,
            ])->save();

            $revoked = 0;

            if ($wasPassed && ! $marked) {
                $revoked = $this->revokeLevelsEvidencedBy($attempt, $actor, $reason);
            }

            GradeOverrideLog::query()->create([
                'quiz_attempt_id' => $attempt->getKey(),
                'trainee_id' => $attempt->user_id,
                'actor_id' => $actor->getKey(),
                'action' => GradeOverrideLog::ACTION_WITHDRAWN,
                'from_passed' => $wasPassed,
                'to_passed' => $marked,
                'score_at_override' => $attempt->score,
                'reason' => $reason,
                'levels_revoked' => $revoked,
                'occurred_at' => now(),
            ]);

            $attempt->refresh();

            $this->recalculate->handle($attempt->user, $attempt->course);

            return $attempt->refresh();
        });
    }

    /**
     * Revoke only the awards this attempt was the stated evidence for.
     *
     * Narrow on purpose. A level earned through a different route stands, and
     * revocation is recorded rather than deleted, so the history survives.
     */
    private function revokeLevelsEvidencedBy(QuizAttempt $attempt, User $actor, string $reason): int
    {
        return TraineeLevel::query()
            ->where('quiz_attempt_id', $attempt->getKey())
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_by' => $actor->getKey(),
                'revoked_reason' => 'Result overturned: '.$reason,
                'updated_at' => now(),
            ]);
    }
}
