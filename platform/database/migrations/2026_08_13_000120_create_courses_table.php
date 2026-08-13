<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();

            // Short line used on the course card; `description` is the full body.
            $table->string('summary', 500)->nullable();
            $table->text('description')->nullable();

            $table->string('thumbnail_path')->nullable();

            // The instructor is a person, not free text, so reporting can group by
            // them. Nulled rather than cascaded — losing a trainer's account must
            // not delete the courses they wrote.
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 20)->default('draft');       // draft|published|archived
            $table->string('difficulty', 20)->default('beginner'); // beginner|intermediate|advanced

            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->boolean('is_required')->default(false);

            // Days from enrollment until the course is due. Null = no deadline.
            $table->unsignedSmallInteger('due_days')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['status', 'is_required']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
