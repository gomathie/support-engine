<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();

            // Every quiz belongs to a course. It may additionally be scoped to a
            // module (end-of-module test) or a single lesson (knowledge check);
            // when both are null it is the course's final assessment.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_module_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Percentage of available points needed to pass.
            $table->unsignedTinyInteger('passing_score')->default(70);

            // Null = unlimited retakes.
            $table->unsignedSmallInteger('max_attempts')->nullable();

            $table->unsignedSmallInteger('time_limit_minutes')->nullable();

            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);

            // Whether the employee sees which questions they got wrong, and the
            // author's explanation, after submitting.
            $table->boolean('show_feedback')->default(true);

            $table->boolean('is_published')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'is_published']);
            $table->index('course_module_id');
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
