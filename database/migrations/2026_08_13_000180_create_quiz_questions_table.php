<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();

            // single_choice | multiple_choice | true_false | short_answer
            $table->string('type', 30)->default('single_choice');

            $table->text('prompt');

            // Shown after submission when the quiz has feedback enabled. Never
            // sent to the browser before the attempt is graded.
            $table->text('explanation')->nullable();

            $table->unsignedSmallInteger('points')->default(1);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['quiz_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
