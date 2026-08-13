<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Keeping the employee's own department nullable: an account can exist
            // before it has been placed, and deleting a department must not delete
            // the people in it.
            $table->foreignId('department_id')
                ->nullable()
                ->after('email_verified_at')
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('employee_number')->nullable()->unique()->after('department_id');
            $table->string('job_title')->nullable()->after('employee_number');

            // Printed on certificates. Falls back to `name` when not set — people
            // often go by a short name day to day and a full legal name on paper.
            $table->string('certificate_name')->nullable()->after('job_title');

            $table->string('theme_preference', 10)->default('light')->after('certificate_name');
            $table->boolean('is_active')->default(true)->after('theme_preference');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->softDeletes();

            $table->index('is_active');
        });

        // A manager owns zero or more departments. Separate from users.department_id,
        // which is where the person themselves sits — a manager may run a department
        // they do not belong to.
        Schema::create('department_manager', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['department_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_manager');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'department_id',
                'employee_number',
                'job_title',
                'certificate_name',
                'theme_preference',
                'is_active',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};
