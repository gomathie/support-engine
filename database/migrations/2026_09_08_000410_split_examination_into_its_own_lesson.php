<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give the examination its own lesson.
 *
 * "Final testing and consultation" carried both the review topics and the
 * examination: two quiz-gated topics, the lesson's own knowledge check and
 * Section A, with Sections B and C waiting behind them. Four sittings under one
 * heading, six once B and C go live.
 *
 * Sitting an exam and reviewing your mistakes are different activities. They are
 * now two lessons:
 *
 *   Lesson 12          Review and consultation
 *   Final examination  PILOT Technical Support Employee Examination
 *
 * Data-only, and written to be safe on a database that has already been
 * re-seeded — every step checks before it acts.
 */
return new class extends Migration
{
    private const EXAM_SUBTITLE = 'PILOT Technical Support Employee Examination';

    public function up(): void
    {
        $course = DB::table('courses')->where('category', 'TRACK 1')->first();

        if (! $course) {
            return;
        }

        // Retitle the review lesson, which no longer holds the exam.
        DB::table('lessons')
            ->where('course_id', $course->id)
            ->where('subtitle', 'Final testing and consultation')
            ->update([
                'subtitle' => 'Review and consultation',
                'description' => 'Open Q&A and working a non-standard case end to end.',
                'updated_at' => now(),
            ]);

        $examLessonId = DB::table('lessons')
            ->where('course_id', $course->id)
            ->where('subtitle', self::EXAM_SUBTITLE)
            ->value('id');

        if (! $examLessonId) {
            $lastPosition = (int) DB::table('lessons')
                ->where('course_id', $course->id)
                ->max('position');

            $examLessonId = DB::table('lessons')->insertGetId([
                'course_id' => $course->id,
                'title' => 'Final examination',
                'subtitle' => self::EXAM_SUBTITLE,
                'description' => 'The official examination. Section A is live; Sections B and C '
                    .'are authored and awaiting a decision on whether Level 1 requires them.',
                'position' => $lastPosition + 1,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Move all three sections onto it.
        DB::table('quizzes')
            ->where('course_id', $course->id)
            ->where('title', 'like', '%Examination%Section%')
            ->update(['lesson_id' => $examLessonId, 'updated_at' => now()]);
    }

    public function down(): void
    {
        $course = DB::table('courses')->where('category', 'TRACK 1')->first();

        if (! $course) {
            return;
        }

        $reviewLessonId = DB::table('lessons')
            ->where('course_id', $course->id)
            ->where('subtitle', 'Review and consultation')
            ->value('id');

        if ($reviewLessonId) {
            DB::table('quizzes')
                ->where('course_id', $course->id)
                ->where('title', 'like', '%Examination%Section%')
                ->update(['lesson_id' => $reviewLessonId, 'updated_at' => now()]);

            DB::table('lessons')
                ->where('id', $reviewLessonId)
                ->update([
                    'subtitle' => 'Final testing and consultation',
                    'description' => 'Assessment and open Q&A.',
                    'updated_at' => now(),
                ]);
        }

        // Only remove the exam lesson if nothing was left hanging off it.
        $examLessonId = DB::table('lessons')
            ->where('course_id', $course->id)
            ->where('subtitle', self::EXAM_SUBTITLE)
            ->value('id');

        if ($examLessonId
            && ! DB::table('topics')->where('lesson_id', $examLessonId)->exists()
            && ! DB::table('quizzes')->where('lesson_id', $examLessonId)->exists()) {
            DB::table('lessons')->where('id', $examLessonId)->delete();
        }
    }
};
