<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the prototype's in-memory STATE object keyed "section:day:item"
     * (pages/training-tracker/script.js), which was written to an undefined
     * window.storage API and therefore never persisted at all.
     */
    public function up(): void
    {
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();

            // Denormalised: course progress is recalculated by course, and this
            // saves a join through modules on the hot path.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // Null means started but not finished.
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'course_id']);
            $table->index(['course_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};
