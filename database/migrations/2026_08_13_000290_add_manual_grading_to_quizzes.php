<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Written and practical answers cannot be machine-marked. An examiner reads
     * them, awards points out of the question's maximum, and leaves feedback —
     * so an attempt now has a state between "submitted" and "scored", and each
     * answer records who marked it and when.
     */
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            // Null until a human has marked it. Distinguishes "awarded zero"
            // from "not yet looked at", which the attempt's score depends on.
            $table->timestamp('graded_at')->nullable()->after('points_awarded');
            $table->foreignId('graded_by')->nullable()->after('graded_at')
                ->constrained('users')->nullOnDelete();
            $table->text('grader_feedback')->nullable()->after('graded_by');

            $table->index('graded_at');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            // Shown to the examiner while marking, never to the employee.
            // `explanation` is the employee-facing text; this is the rubric.
            $table->text('marking_guidance')->nullable()->after('explanation');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Points that are already settled, so the grading screen can show
            // progress without recomputing the objective half every time.
            $table->unsignedSmallInteger('auto_points_earned')->nullable()->after('points_earned');
            $table->unsignedSmallInteger('manual_points_possible')->nullable()->after('points_possible');
            $table->timestamp('reviewed_at')->nullable()->after('completed_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['auto_points_earned', 'manual_points_possible', 'reviewed_at', 'reviewed_by']);
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('marking_guidance');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropForeign(['graded_by']);
            $table->dropIndex(['graded_at']);
            $table->dropColumn(['graded_at', 'graded_by', 'grader_feedback']);
        });
    }
};
