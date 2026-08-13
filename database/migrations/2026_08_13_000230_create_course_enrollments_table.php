<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // manual | department | role | rule | self
            $table->string('source', 20)->default('manual');

            // Set when source = rule, so revoking a rule can find exactly the
            // enrollments it created and leave manual ones alone.
            $table->foreignId('assignment_rule_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('enrolled_at');
            $table->timestamp('due_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // One enrollment per person per course. Re-assigning an unenrolled
            // employee restores the soft-deleted row rather than duplicating it.
            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'due_at']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
