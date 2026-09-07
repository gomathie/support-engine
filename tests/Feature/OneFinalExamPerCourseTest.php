<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use Database\Seeders\LessonContentSeeder;
use Database\Seeders\PilotExamSeeder;
use Database\Seeders\TrainingContentSeeder;
use Database\Seeders\WrittenExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A course has at most one final exam.
 *
 * `RecalculateCourseProgress` reads `finalQuiz()->first()`, so a second
 * course-scoped quiz does not become a second gate — it becomes a quiz nobody
 * has to pass, sitting in front of trainees looking exactly like one they do.
 * That is worse than either having it or not.
 *
 * This regressed once already: two seeders each created a course-level exam.
 */
class OneFinalExamPerCourseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TrainingContentSeeder::class);
        $this->seed(LessonContentSeeder::class);
        $this->seed(PilotExamSeeder::class);
        $this->seed(WrittenExamSeeder::class);
    }

    public function test_no_course_has_more_than_one_published_final_exam(): void
    {
        foreach (Course::query()->get() as $course) {
            $finals = $course->finalQuiz()->where('is_published', true)->get();

            $this->assertLessThanOrEqual(
                1,
                $finals->count(),
                "\"{$course->title}\" has ".$finals->count().' published final exams: '
                .$finals->pluck('title')->implode(' · ')
                .'. Only the first gates completion; the rest are sittable but count for nothing.',
            );
        }
    }

    /**
     * Section A is live and gates the final lesson.
     *
     * Its answer key was confirmed on 2026-09-08, closing PA-16. It is scoped
     * to the final lesson rather than the course, because the course already
     * has a final exam — leaving it course-scoped made it a second one, and
     * only the first of those gates anything.
     */
    public function test_the_official_examination_is_live_and_actually_gates(): void
    {
        $sectionA = Quiz::query()
            ->where('title', 'like', '%Section A%')
            ->first();

        if (! $sectionA) {
            $this->markTestSkipped('Section A is not seeded in this environment.');
        }

        $this->assertTrue((bool) $sectionA->is_published);

        $this->assertNotNull(
            $sectionA->lesson_id,
            'Section A must be lesson-scoped. Course-scoped would make it a second final exam, '
            .'which RecalculateCourseProgress ignores.',
        );

        $this->assertNull($sectionA->topic_id);
    }

    /** Whatever does gate the course should be the one with authored answers. */
    public function test_the_gating_exam_is_the_one_with_verified_answers(): void
    {
        $course = Course::query()->where('title', '1st-line support')->firstOrFail();

        $final = $course->finalQuiz()->where('is_published', true)->first();

        $this->assertNotNull($final, 'The course needs a final exam to gate on.');
        $this->assertSame('PILOT 1st-line final assessment', $final->title);
    }
}
