<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A rollup, recalculated by App\Actions\Progress\RecalculateCourseProgress
     * whenever a lesson is completed or an attempt is graded. Denormalised on
     * purpose: the dashboard and every report read it directly, and recomputing
     * percentages across all enrollments per request does not scale.
     */
    public function up(): void
    {
        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // not_started | in_progress | completed | failed | overdue
            $table->string('status', 20)->default('not_started');

            $table->unsignedInteger('completed_lessons')->default(0);
            $table->unsignedInteger('total_lessons')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);

            // Final-assessment outcome, mirrored here so the dashboard does not
            // have to reach into quiz_attempts.
            $table->decimal('final_score', 5, 2)->nullable();
            $table->unsignedSmallInteger('quiz_attempts_count')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_progress');
    }
};
