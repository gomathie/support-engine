<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();

            // Restricted, not cascaded: deleting a question must not silently
            // rewrite the history of attempts already graded against it. Authors
            // soft-delete questions instead.
            $table->foreignId('quiz_question_id')->constrained()->restrictOnDelete();

            // Option ids the employee chose. jsonb so a single column serves
            // single-choice, multiple-choice and true/false alike.
            $table->jsonb('selected_option_ids')->nullable();

            $table->text('text_answer')->nullable();

            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('points_awarded')->default(0);

            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'quiz_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
