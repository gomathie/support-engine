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

    /**
     * Superseded, and kept as the record of why.
     *
     * A course used to be limited to one final exam because
     * `RecalculateCourseProgress` read `finalQuiz()->first()` — a second one was
     * sittable and counted for nothing. Completion now requires *every*
     * published final exam, so several is a legitimate design and the PILOT
     * examination uses it: three papers, all required.
     *
     * What still has to hold is that none of them is ignored.
     */
    public function test_every_published_final_exam_actually_gates_completion(): void
    {
        foreach (Course::query()->get() as $course) {
            $finals = $course->finalQuiz()->where('is_published', true)->get();

            if ($finals->isEmpty()) {
                continue;
            }

            $trainee = $this->trainee();
            app(\App\Actions\Enrollment\EnrollEmployee::class)->handle($trainee, $course);

            // Nothing has been sat, so no final can read as satisfied.
            foreach ($finals as $final) {
                $this->assertFalse(
                    $final->passedBy($trainee),
                    "\"{$final->title}\" should be outstanding for a new trainee.",
                );
            }

            app(\App\Actions\Progress\RecalculateCourseProgress::class)->handle($trainee, $course);

            $this->assertNotSame(
                \App\Enums\ProgressStatus::Completed,
                $trainee->courseProgress()->where('course_id', $course->id)->first()?->status,
                "\"{$course->title}\" completed with every final exam unsat.",
            );
        }
    }

    /**
     * Section A is live and is a final exam.
     *
     * Its answer key was confirmed on 2026-09-08, closing PA-16. It was briefly
     * moved to module scope as a workaround while a course could only have one
     * final exam — that limit is gone, and it is classified correctly again.
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

        $this->assertNull($sectionA->module_id, 'Section A is a final exam, not a module quiz.');
        $this->assertNull($sectionA->lesson_id);

        $course = Course::query()->where('slug', '1st-line-support')->firstOrFail();

        $this->assertTrue(
            $course->finalQuiz()->where('is_published', true)
                ->pluck('id')->contains($sectionA->id),
            'Section A must be among the final exams that gate completion.',
        );
    }

    /** The internally authored assessment is still one of the papers. */
    public function test_the_internal_assessment_remains_a_final_exam(): void
    {
        $course = Course::query()->where('slug', '1st-line-support')->firstOrFail();

        $titles = $course->finalQuiz()->where('is_published', true)->pluck('title');

        $this->assertTrue(
            $titles->contains('PILOT 1st-line final assessment'),
            'Retiring it is a content decision; until then it gates like the rest.',
        );
    }
}
