<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A module has one knowledge check. Enforce it, and keep it enforced.
 *
 * `2026_09_08_000470` merged thirteen duplicates left by the lesson-to-module
 * rename, matching titles that began "Lesson N". It fixed those thirteen and
 * not the cause: `LessonContentSeeder` matched a module's knowledge check
 * **by title**, so any retitle created a second one rather than renaming the
 * first. It happened again immediately — "Module 4 — Final Assessment" against
 * "Module 4 — Final assessment", a difference of one letter's case.
 *
 * That is not cosmetic. `RecalculateCourseProgress` and `QuizPolicy` both
 * require every published module-scoped quiz to be passed, so each duplicate
 * silently doubles a module's requirement, with the same questions twice.
 *
 * The seeder now keys on the module, so a retitle is a rename. This clears
 * whatever the title-matching era left behind, by the same rules as before:
 *
 *   · If exactly one of a module's checks has been attempted, that one
 *     survives — graded history is what must not move.
 *   · Otherwise the oldest survives, being the one anything else is most
 *     likely to point at.
 *   · A quiz with attempts is never deleted, and if two have been attempted
 *     neither is touched: that needs a person, not a migration.
 *
 * `partial_unique` would be the belt to this braces — a unique index on
 * (module_id) where lesson_id is null and deleted_at is null. It is not added
 * here because a module legitimately carried more than one check for a while
 * and an environment mid-repair would fail to migrate. Worth revisiting once
 * every environment is known clean.
 */
return new class extends Migration
{
    public function up(): void
    {
        $grouped = DB::table('quizzes')
            ->whereNull('deleted_at')
            ->whereNull('lesson_id')
            ->whereNotNull('module_id')
            ->orderBy('id')
            ->get()
            ->groupBy('module_id')
            ->filter(fn ($checks) => $checks->count() > 1);

        foreach ($grouped as $checks) {
            $attempted = $checks->filter(fn ($check) => $this->attempts($check->id) > 0);

            if ($attempted->count() > 1) {
                continue;
            }

            $keep = $attempted->first() ?? $checks->first();

            foreach ($checks as $check) {
                if ($check->id === $keep->id) {
                    continue;
                }

                DB::table('quiz_questions')->where('quiz_id', $check->id)->delete();
                DB::table('quizzes')->where('id', $check->id)->delete();
            }
        }
    }

    /** Not reversed: restoring a duplicate that was gating a course is not a fix. */
    public function down(): void
    {
        //
    }

    private function attempts(int $quizId): int
    {
        return DB::table('quiz_attempts')->where('quiz_id', $quizId)->count();
    }
};
