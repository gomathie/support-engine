<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();

            // Composition: a module has no meaning outside its course.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->string('title');

            // The prototype's day cards carry a short label ("Day 1", "Module A")
            // alongside a longer heading. Both are kept.
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
