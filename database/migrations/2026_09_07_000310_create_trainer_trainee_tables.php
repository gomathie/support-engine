<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who trains whom.
 *
 * Until now a trainer's reach was their department, which conflated two
 * different questions: "does this person work near me" and "am I responsible
 * for their training". A cohort is an explicit, auditable assignment made by an
 * Admin.
 *
 * History is kept rather than overwritten. An assignment ends by setting
 * ended_at, never by deleting the row — a completed grade has to stay
 * attributable to whoever held the cohort at the time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_trainee', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');

            // Null means current. A trainee may have many closed assignments
            // and at most one open one — enforced by the partial index below.
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            $table->index(['trainer_id', 'ended_at']);
            $table->index(['trainee_id', 'ended_at']);
        });

        /*
         * One active trainer per trainee. A plain unique index cannot express
         * this because it would also forbid a second *closed* assignment, so
         * this is a partial index over the open rows only.
         *
         * Postgres-specific; this project is Postgres in every environment
         * including the test suite, on purpose.
         */
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX trainer_trainee_one_active_per_trainee
            ON trainer_trainee (trainee_id)
            WHERE ended_at IS NULL
        SQL);

        Schema::create('trainer_assignment_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();

            // Both nullable: the first assignment has no previous trainer, and
            // an unassignment has no new one.
            $table->foreignId('from_trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_trainer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 20);           // assigned | reassigned | unassigned
            $table->text('reason')->nullable();

            // How much grading moved with the trainee. Recorded at the moment
            // of the change because it cannot be reconstructed afterwards.
            $table->unsignedInteger('pending_grading_inherited')->default(0);

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['trainee_id', 'occurred_at']);
            $table->index('to_trainer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_assignment_logs');
        Schema::dropIfExists('trainer_trainee');
    }
};
