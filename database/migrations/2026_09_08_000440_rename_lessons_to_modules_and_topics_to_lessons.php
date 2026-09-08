<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settle on course → module → lesson.
 *
 *     lessons  →  modules     (a unit of study: "Module 1")
 *     topics   →  lessons     (one page of content within it)
 *
 * This is close to a reversal of `2026_09_07_000400`, which renamed
 * course_modules → lessons → topics. That pass removed a genuine ambiguity —
 * two things called a lesson — but landed on "topic" for the page, and
 * "module → lesson" reads better for a curriculum than "lesson → topic".
 *
 * **Order matters, the other way round this time.** `lessons` has to vacate
 * its name before `topics` can take it, so every lesson→module rename happens
 * first and completely.
 *
 * A rename rather than rewritten history, as before: existing databases keep
 * their rows, indexes and foreign keys, which PostgreSQL carries across.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. lesson → module, everywhere ─────────────────────
        Schema::rename('lessons', 'modules');

        $this->renameColumn('topics', 'lesson_id', 'module_id');
        $this->renameColumn('quizzes', 'lesson_id', 'module_id');

        // ─── 2. topic → lesson, now the name is free ────────────
        Schema::rename('topics', 'lessons');
        Schema::rename('topic_progress', 'lesson_progress');
        Schema::rename('topic_annotations', 'lesson_annotations');
        Schema::rename('topic_resources', 'lesson_resources');

        $this->renameColumn('lesson_progress', 'topic_id', 'lesson_id');
        $this->renameColumn('lesson_annotations', 'topic_id', 'lesson_id');
        $this->renameColumn('lesson_resources', 'topic_id', 'lesson_id');
        $this->renameColumn('quizzes', 'topic_id', 'lesson_id');
        $this->renameColumn('practical_tasks', 'topic_id', 'lesson_id');

        $this->renameColumn('course_progress', 'completed_topics', 'completed_lessons');
        $this->renameColumn('course_progress', 'total_topics', 'total_lessons');

        // The units are titled after what they are.
        DB::table('modules')
            ->where('title', 'like', 'Lesson %')
            ->update([
                'title' => DB::raw("replace(title, 'Lesson ', 'Module ')"),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('modules')
            ->where('title', 'like', 'Module %')
            ->update([
                'title' => DB::raw("replace(title, 'Module ', 'Lesson ')"),
                'updated_at' => now(),
            ]);

        $this->renameColumn('course_progress', 'total_lessons', 'total_topics');
        $this->renameColumn('course_progress', 'completed_lessons', 'completed_topics');

        $this->renameColumn('practical_tasks', 'lesson_id', 'topic_id');
        $this->renameColumn('quizzes', 'lesson_id', 'topic_id');
        $this->renameColumn('lesson_resources', 'lesson_id', 'topic_id');
        $this->renameColumn('lesson_annotations', 'lesson_id', 'topic_id');
        $this->renameColumn('lesson_progress', 'lesson_id', 'topic_id');

        Schema::rename('lesson_resources', 'topic_resources');
        Schema::rename('lesson_annotations', 'topic_annotations');
        Schema::rename('lesson_progress', 'topic_progress');
        Schema::rename('lessons', 'topics');

        $this->renameColumn('quizzes', 'module_id', 'lesson_id');
        $this->renameColumn('topics', 'module_id', 'lesson_id');

        Schema::rename('modules', 'lessons');
    }

    /**
     * Raw SQL rather than the schema builder: `ALTER TABLE … RENAME COLUMN`
     * keeps indexes, constraints and data exactly as they are, which is the
     * whole point of doing this as a rename.
     */
    private function renameColumn(string $table, string $from, string $to): void
    {
        if (! Schema::hasColumn($table, $from)) {
            return;
        }

        DB::statement("ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}");
    }
};
