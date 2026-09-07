<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The competency ladder: Basic / Second / Third, held per area of expertise.
 *
 * The question this exists to answer is "who is Level 1 in Sensors" — which
 * needs both a rung and a subject, because somebody can be competent at
 * reporting and not at devices. A single "level" column on the user could not
 * express that.
 *
 * Distinct from `courses.difficulty`, which stays. Difficulty is a descriptive
 * label on one course ("this is advanced material"); a level is a structural
 * award earned by completing a defined set of courses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Basic, Second, Third
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // The ladder order. Rung 2 is not awarded before rung 1.
            $table->unsignedTinyInteger('position')->unique();

            $table->timestamps();
        });

        Schema::create('competency_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Objects & Sensors, Reporting…
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        /*
         * What a level demands. One row per course required for a given
         * (level, area) pair — a level is earned by finishing a set of courses,
         * not a single one, which is why this is a table rather than a column.
         */
        Schema::create('level_requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['level_id', 'competency_area_id', 'course_id'], 'level_requirement_unique');
            $table->index(['level_id', 'competency_area_id']);
        });

        /*
         * The award itself — evidence that a person reached a rung in an area.
         *
         * `quiz_attempt_id` is the evidence trail: which sitting earned it.
         * Restricted on delete, because an award must stay explicable.
         */
        Schema::create('trainee_levels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->restrictOnDelete();
            $table->foreignId('competency_area_id')->constrained()->restrictOnDelete();

            $table->timestamp('awarded_at');

            // Null when the system awarded it on a passing exam; set when a
            // trainer or admin granted it by hand.
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('quiz_attempt_id')->nullable()->constrained()->nullOnDelete();

            // Withdrawn rather than deleted, so the history survives.
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revoked_reason')->nullable();

            $table->timestamps();

            // One award per person per rung per area. A revoked row keeps the
            // slot; re-awarding clears revoked_at rather than inserting again.
            $table->unique(['user_id', 'level_id', 'competency_area_id'], 'trainee_level_unique');
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::table('courses', function (Blueprint $table) {
            // Nullable: plenty of courses are standalone and award nothing.
            $table->foreignId('level_id')->nullable()->after('category')
                ->constrained()->nullOnDelete();
            $table->foreignId('competency_area_id')->nullable()->after('level_id')
                ->constrained()->nullOnDelete();

            $table->index(['level_id', 'competency_area_id']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropForeign(['competency_area_id']);
            $table->dropIndex(['level_id', 'competency_area_id']);
            $table->dropColumn(['level_id', 'competency_area_id']);
        });

        Schema::dropIfExists('trainee_levels');
        Schema::dropIfExists('level_requirements');
        Schema::dropIfExists('competency_areas');
        Schema::dropIfExists('levels');
    }
};
