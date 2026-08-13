<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();

            $table->text('label');

            // For short_answer questions the rows here are the accepted answers
            // rather than presented choices; all of them carry is_correct = true
            // and are matched case-insensitively at grading time.
            $table->boolean('is_correct')->default(false);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['quiz_question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_options');
    }
};
