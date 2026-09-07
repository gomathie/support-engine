<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make the schema say what the product says.
 *
 * The curriculum is a course made of lessons, each lesson made of topics. The
 * schema said course → module → lesson, and once the modules were retitled
 * "Lesson 1 … Lesson 12" the admin panel carried two different things both
 * called a lesson. This is a straight rename to remove that:
 *
 *     course_modules  →  lessons     (a unit of study: "Lesson 1")
 *     lessons         →  topics      (one page of content within it)
 *
 * **Order matters.** `lessons` has to vacate the name before `course_modules`
 * can take it, so every lesson→topic rename happens first and completely. Doing
 * it the other way round collides.
 *
 * A rename rather than rewritten history: the earlier migrations still create
 * `course_modules` and `lessons`, because that is what they did. Any database
 * that has already run them gets moved forward by this one, keeping its data,
 * its indexes and its foreign keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. lesson → topic, everywhere ──────────────────────
        Schema::rename('lessons', 'topics');
        Schema::rename('lesson_progress', 'topic_progress');
        Schema::rename('lesson_annotations', 'topic_annotations');
        Schema::rename('lesson_resources', 'topic_resources');

        $this->renameColumn('topic_progress', 'lesson_id', 'topic_id');
        $this->renameColumn('topic_annotations', 'lesson_id', 'topic_id');
        $this->renameColumn('topic_resources', 'lesson_id', 'topic_id');
        $this->renameColumn('quizzes', 'lesson_id', 'topic_id');
        $this->renameColumn('practical_tasks', 'lesson_id', 'topic_id');

        // The rollup counters count topics, not lessons.
        $this->renameColumn('course_progress', 'completed_lessons', 'completed_topics');
        $this->renameColumn('course_progress', 'total_lessons', 'total_topics');

        // ─── 2. module → lesson, now the name is free ───────────
        Schema::rename('course_modules', 'lessons');

        $this->renameColumn('topics', 'course_module_id', 'lesson_id');
        $this->renameColumn('quizzes', 'course_module_id', 'lesson_id');
    }

    public function down(): void
    {
        $this->renameColumn('quizzes', 'lesson_id', 'course_module_id');
        $this->renameColumn('topics', 'lesson_id', 'course_module_id');

        Schema::rename('lessons', 'course_modules');

        $this->renameColumn('course_progress', 'total_topics', 'total_lessons');
        $this->renameColumn('course_progress', 'completed_topics', 'completed_lessons');

        $this->renameColumn('practical_tasks', 'topic_id', 'lesson_id');
        $this->renameColumn('quizzes', 'topic_id', 'lesson_id');
        $this->renameColumn('topic_resources', 'topic_id', 'lesson_id');
        $this->renameColumn('topic_annotations', 'topic_id', 'lesson_id');
        $this->renameColumn('topic_progress', 'topic_id', 'lesson_id');

        Schema::rename('topic_resources', 'lesson_resources');
        Schema::rename('topic_annotations', 'lesson_annotations');
        Schema::rename('topic_progress', 'lesson_progress');
        Schema::rename('topics', 'lessons');
    }

    /**
     * Raw SQL rather than the schema builder.
     *
     * `$table->renameColumn()` needs doctrine/dbal to inspect the column first
     * and rebuilds indexes in the process. `ALTER TABLE … RENAME COLUMN` keeps
     * the indexes, constraints and data exactly as they are, which is the whole
     * point of doing this as a rename.
     */
    private function renameColumn(string $table, string $from, string $to): void
    {
        if (! Schema::hasColumn($table, $from)) {
            return;
        }

        DB::statement("ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}");
    }
};
