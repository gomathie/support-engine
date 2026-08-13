<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();

            // Denormalised so "every lesson in this course" and the progress
            // recalculation do not need to join through modules on every request.
            // Kept in sync by the Lesson model.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            // Open set by design — new content types are added here without a
            // schema change. See App\Enums\LessonType.
            $table->string('type', 30)->default('rich_text');

            // Sanitised HTML for rich_text lessons; null for the file-backed types,
            // which carry their payload in lesson_resources.
            $table->longText('content')->nullable();

            $table->string('external_url')->nullable();

            $table->unsignedInteger('estimated_minutes')->nullable();

            // view      — reading it is enough
            // acknowledge — employee must actively confirm
            // quiz      — the attached quiz must be passed
            $table->string('completion_requirement', 20)->default('view');

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_module_id', 'slug']);
            $table->index(['course_module_id', 'position']);
            $table->index(['course_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
