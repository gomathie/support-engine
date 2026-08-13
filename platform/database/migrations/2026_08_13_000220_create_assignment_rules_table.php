<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Department: Operations -> automatically assign Fleet Safety Training".
     * Rules are data, never code, so an administrator can add one without a
     * deploy. Evaluated by App\Actions\Enrollment\SyncAssignmentRules.
     */
    public function up(): void
    {
        Schema::create('assignment_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // department | role | user | all
            $table->string('target_type', 20);

            // departments.id or users.id, depending on target_type. Null for
            // target_type = role (which uses target_value) and = all.
            $table->unsignedBigInteger('target_id')->nullable();

            // The role name, for target_type = role.
            $table->string('target_value')->nullable();

            // Overrides courses.due_days for people enrolled through this rule.
            $table->unsignedSmallInteger('due_days')->nullable();

            $table->boolean('is_active')->default(true);

            // Whether existing staff are enrolled when the rule is created, or
            // only people who join the target afterwards.
            $table->boolean('applies_retroactively')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['course_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_rules');
    }
};
