<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pass/fail override with a recorded reason (PA-12).
 *
 * §3 of the plan: promotion is automatic, and the override exists for the
 * genuine exceptions — a pass secured on a question later found defective, or a
 * fail a Trainer wants to overturn. Both need a reason and both need to be
 * reconstructable afterwards.
 *
 * The override is stored on the attempt rather than written straight into
 * `passed`, so that re-grading a written answer — which re-runs
 * FinaliseQuizAttempt — cannot silently undo a human decision. Finalisation
 * applies the override when it sets `passed`, so everything downstream keeps
 * reading one authoritative column.
 *
 * The score is deliberately never overwritten: "scored 62% but passed on
 * appeal" is the truth, and flattening it to 70% would erase the appeal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Null = no override, the marked result stands.
            $table->boolean('override_passed')->nullable()->after('passed');
            $table->text('override_reason')->nullable()->after('override_passed');
            $table->foreignId('overridden_by')->nullable()->after('override_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable()->after('overridden_by');
        });

        /*
         * The immutable record. §6 asks for an audit log covering grading
         * actions and pass/fail overrides; this is the second half of it, and
         * it is written on every change including a withdrawal.
         */
        Schema::create('grade_override_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();

            // Denormalised so "every override touching this trainee" does not
            // have to join through attempts.
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 20);            // overridden | withdrawn

            // What the state was and what it became. Kept as booleans rather
            // than recomputed later, because the quiz's pass mark can change.
            $table->boolean('from_passed')->nullable();
            $table->boolean('to_passed')->nullable();
            $table->decimal('score_at_override', 5, 2)->nullable();

            $table->text('reason');

            // Levels whose evidence was this attempt and which were revoked as
            // a consequence. Recorded here because it cannot be reconstructed.
            $table->unsignedInteger('levels_revoked')->default(0);

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['trainee_id', 'occurred_at']);
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_override_logs');

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['overridden_by']);
            $table->dropColumn([
                'override_passed',
                'override_reason',
                'overridden_by',
                'overridden_at',
            ]);
        });
    }
};
