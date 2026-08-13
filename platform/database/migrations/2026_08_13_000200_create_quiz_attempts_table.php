<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();

            // Denormalised for reporting: "average score by course" should not have
            // to join quizzes on every row.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('attempt_number');

            $table->string('status', 20)->default('in_progress'); // in_progress|completed|abandoned

            // Percentage, 0.00–100.00. Null while the attempt is unfinished.
            $table->decimal('score', 5, 2)->nullable();

            $table->unsignedSmallInteger('points_earned')->nullable();
            $table->unsignedSmallInteger('points_possible')->nullable();

            $table->boolean('passed')->nullable();

            // Snapshot of the pass mark in force when the attempt was taken, so
            // later edits to the quiz cannot retroactively fail somebody.
            $table->unsignedTinyInteger('passing_score')->nullable();

            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'quiz_id', 'attempt_number']);
            $table->index(['quiz_id', 'status']);
            $table->index(['user_id', 'course_id']);
            $table->index(['course_id', 'passed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
