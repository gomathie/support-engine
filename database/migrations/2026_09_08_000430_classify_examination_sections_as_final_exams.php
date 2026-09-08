<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The three examination sections are final exams, not lesson quizzes.
 *
 * They were scoped to the "Final examination" lesson, which worked but said the
 * wrong thing: a lesson quiz is a knowledge check on the material just read,
 * and this is the examination the whole course leads to. Classifying them
 * properly means `lesson_id` and `topic_id` both null.
 *
 * **This only became safe once a course could have more than one.** Until now
 * `RecalculateCourseProgress` read `finalQuiz()->first()`, so a second
 * course-scoped quiz was sittable and counted for nothing — the bug that hid
 * Section A in plain sight. That method now requires *every* published
 * course-scoped quiz to be passed, which is what makes this a reclassification
 * rather than a regression.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('quizzes')
            ->where('title', 'like', '%Examination%Section%')
            ->update([
                'lesson_id' => null,
                'topic_id' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $lessonId = DB::table('lessons')
            ->where('subtitle', 'PILOT Technical Support Employee Examination')
            ->value('id');

        if (! $lessonId) {
            return;
        }

        DB::table('quizzes')
            ->where('title', 'like', '%Examination%Section%')
            ->update([
                'lesson_id' => $lessonId,
                'topic_id' => null,
                'updated_at' => now(),
            ]);
    }
};
