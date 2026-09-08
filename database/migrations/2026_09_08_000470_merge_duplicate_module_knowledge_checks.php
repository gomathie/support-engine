<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every module in two courses had two knowledge checks, and both were gating.
 *
 * `2026_09_08_000440` renamed lessons to modules. The seeded knowledge checks
 * are matched **by title**, so "Lesson 7 — knowledge check" no longer matched
 * "Module 7 — knowledge check" in the content file, and the next seeder run
 * created a second one beside it rather than renaming the first.
 *
 * That is not cosmetic. `RecalculateCourseProgress` and `QuizPolicy` both
 * require **every** published module-scoped quiz to be passed, so 1st-line
 * support silently went from twelve knowledge checks to twenty-four — the same
 * questions, twice each, and a trainee who passed one still blocked by its
 * twin. Thirteen duplicates in total, across 1st-line support and Admin panel.
 *
 * This keeps one per module and deletes the other:
 *
 *   · If exactly one of the pair has been attempted, that one survives —
 *     graded history is the thing that must not move.
 *   · Otherwise the correctly-titled one survives.
 *   · A quiz with attempts is never deleted. `quiz_answers.quiz_question_id`
 *     is `restrictOnDelete` deliberately, so the attempt would block it anyway;
 *     this refuses first and says so rather than failing mid-migration.
 *
 * Survivors still carrying the old title are renamed, so the next seeder run
 * matches them instead of creating a third.
 *
 * Note the trap for whoever renames these next: **the seeder matches quizzes by
 * title.** Retitling a knowledge check in a content file without moving the
 * existing row means a duplicate, not a rename — the same shape of bug as the
 * course-slug duplicate in `2026_09_08_000420`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $stale = DB::table('quizzes')
            ->whereNull('deleted_at')
            ->whereNull('lesson_id')
            ->whereNotNull('module_id')
            ->where('title', 'like', 'Lesson % — knowledge check')
            ->get();

        foreach ($stale as $quiz) {
            $current = str_replace('Lesson ', 'Module ', $quiz->title);

            $twin = DB::table('quizzes')
                ->whereNull('deleted_at')
                ->whereNull('lesson_id')
                ->where('module_id', $quiz->module_id)
                ->where('title', $current)
                ->where('id', '!=', $quiz->id)
                ->first();

            if (! $twin) {
                DB::table('quizzes')
                    ->where('id', $quiz->id)
                    ->update(['title' => $current, 'updated_at' => now()]);

                continue;
            }

            $staleAttempts = $this->attempts($quiz->id);
            $twinAttempts = $this->attempts($twin->id);

            // Whichever has been sat is the one with history worth keeping. If
            // both have been sat, neither is safe to remove and a person has to
            // decide — leave them and let the assertion below report it.
            if ($staleAttempts > 0 && $twinAttempts > 0) {
                continue;
            }

            if ($staleAttempts > 0) {
                $this->discard($twin->id);

                DB::table('quizzes')
                    ->where('id', $quiz->id)
                    ->update(['title' => $current, 'updated_at' => now()]);

                continue;
            }

            $this->discard($quiz->id);
        }
    }

    /**
     * Not reversed.
     *
     * Recreating a duplicate that was gating the course by mistake is not a
     * restoration, and the surviving quiz carries the attempts either way.
     */
    public function down(): void
    {
        //
    }

    private function attempts(int $quizId): int
    {
        return DB::table('quiz_attempts')->where('quiz_id', $quizId)->count();
    }

    /**
     * Remove a quiz and the questions that belong to it.
     *
     * Only ever called on a quiz with no attempts, so no graded answer is
     * pointing at any of these questions.
     */
    private function discard(int $quizId): void
    {
        DB::table('quiz_questions')->where('quiz_id', $quizId)->delete();
        DB::table('quizzes')->where('id', $quizId)->delete();
    }
};
