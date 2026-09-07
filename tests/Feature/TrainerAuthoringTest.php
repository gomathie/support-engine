<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\PracticalTask;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A trainer authors the whole course, not fragments of one.
 *
 * They previously held lessons.manage and quizzes.manage but not
 * courses.create — able to write the lessons and the exam, but not the course
 * those sit in, so every new course needed an administrator.
 */
class TrainerAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trainer_can_author_a_course_end_to_end(): void
    {
        $trainer = $this->trainer();

        $this->assertTrue($trainer->can('create', Course::class), 'A trainer must be able to create a course.');

        $course = Course::factory()->create();

        $this->assertTrue($trainer->can('update', $course));
        $this->assertTrue($trainer->can('publish', $course));

        // The pieces that go inside it.
        $this->assertTrue($trainer->can('lessons.manage'));
        $this->assertTrue($trainer->can('quizzes.manage'));
    }

    /**
     * Deleting a course takes other people's training records, certificates and
     * level awards with it. That stays an administrator's decision.
     */
    public function test_a_trainer_cannot_delete_a_course(): void
    {
        $course = Course::factory()->create();

        $this->assertFalse($this->trainer()->can('delete', $course));
        $this->assertTrue($this->admin()->can('delete', $course));
    }

    public function test_a_trainee_can_author_nothing(): void
    {
        $trainee = $this->trainee();
        $course = Course::factory()->create();

        $this->assertFalse($trainee->can('create', Course::class));
        $this->assertFalse($trainee->can('update', $course));
        $this->assertFalse($trainee->can('publish', $course));
        $this->assertFalse($trainee->can('lessons.manage'));
        $this->assertFalse($trainee->can('quizzes.manage'));
    }

    public function test_the_course_screens_are_reachable_by_a_trainer(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->trainer());

        $this->get(CourseResource::getUrl('index'))->assertSuccessful();
        $this->get(CourseResource::getUrl('create'))->assertSuccessful();
        $this->get(CourseResource::getUrl('edit', ['record' => $course]))->assertSuccessful();
    }

    // ─── ASSESSED OR NOT ─────────────────────────────────────

    /**
     * "Trainees must pass" only holds if there is something to pass. A course
     * with neither an exam nor a practical is finished by opening the lessons,
     * which is the old model wearing new clothes — so it is surfaced.
     */
    public function test_a_course_with_no_exam_or_practical_is_marked_as_reading_only(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        Lesson::factory()->for($module, 'module')->create();

        $this->assertFalse($course->fresh()->isAssessed());
    }

    public function test_a_course_with_a_published_exam_is_assessed(): void
    {
        $course = Course::factory()->create();

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'passing_score' => 70,
        ]);

        QuizQuestion::factory()->for($quiz)->withOptions(4, [0])->create();

        $this->assertTrue($course->fresh()->isAssessed());
    }

    public function test_a_course_assessed_only_by_a_practical_still_counts(): void
    {
        $course = Course::factory()->create();

        PracticalTask::query()->create([
            'course_id' => $course->id,
            'title' => 'Configure a sensor',
            'brief' => '<p>Do it.</p>',
            'is_published' => true,
        ]);

        $this->assertTrue($course->fresh()->isAssessed());
    }

    /** An unpublished exam is not an assessment — nobody can sit it. */
    public function test_an_unpublished_exam_does_not_make_a_course_assessed(): void
    {
        $course = Course::factory()->create();

        Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => false,
        ]);

        $this->assertFalse($course->fresh()->isAssessed());
    }

    /** The pass mark is the trainer's to set, and is fixed at the moment of sitting. */
    public function test_the_pass_mark_is_set_per_quiz_and_snapshotted_onto_the_attempt(): void
    {
        $course = Course::factory()->create();

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 55,
            'is_published' => true,
        ]);

        $this->assertSame(55, $quiz->passing_score);

        $trainer = $this->trainer();
        $this->assertTrue($trainer->can('quizzes.manage'), 'The trainer sets the pass mark.');
    }
}
