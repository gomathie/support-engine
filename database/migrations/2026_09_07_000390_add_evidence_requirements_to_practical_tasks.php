<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verifiable evidence on practical tasks.
 *
 * Trainees work in a real PILOT account, so when a task says "create an object"
 * there is a real object afterwards with a real identifier. Asking for that
 * identifier turns the submission from a claim into something a trainer can go
 * and check: paste the agent ID into PILOT and the vehicle is either there or
 * it is not.
 *
 * That matters more here than usual. Verification is one of the four rubric
 * criteria, and a written account of what somebody did is weak evidence for it —
 * a screenshot plus the identifier is strong.
 *
 * `required_evidence` is a list of fields the task demands, each
 * {key, label, hint}. Free-form rather than fixed columns because the fields
 * differ per task: creating an object wants an agent ID, creating a user wants
 * an account ID, configuring a sensor wants both plus a sensor name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practical_tasks', function (Blueprint $table) {
            $table->jsonb('required_evidence')->nullable()->after('expected_evidence');

            // A task where the proof is visual — "show me the sensor tab after
            // your fix" — refuses a submission with nothing attached.
            $table->boolean('requires_screenshot')->default(false)->after('required_evidence');
        });

        Schema::table('practical_submissions', function (Blueprint $table) {
            // The identifiers the trainee supplied, keyed by the task's field
            // keys. Kept on the submission rather than parsed out of the
            // write-up, so a trainer can check them without reading prose.
            $table->jsonb('evidence')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('practical_submissions', function (Blueprint $table) {
            $table->dropColumn('evidence');
        });

        Schema::table('practical_tasks', function (Blueprint $table) {
            $table->dropColumn(['required_evidence', 'requires_screenshot']);
        });
    }
};
