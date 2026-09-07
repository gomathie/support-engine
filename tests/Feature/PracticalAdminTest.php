<?php

namespace Tests\Feature;

use App\Actions\Cohorts\AssignTrainee;
use App\Actions\Practical\SubmitPracticalTask;
use App\Enums\SubmissionStatus;
use App\Filament\Resources\PracticalSubmissions\PracticalSubmissionResource;
use App\Filament\Resources\PracticalTasks\PracticalTaskResource;
use App\Models\Course;
use App\Models\PracticalSubmission;
use App\Models\PracticalTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin surface for practical tasks (PA-14).
 *
 * The boundary that matters: the marking queue shows what a trainer can act on.
 * A list full of rows you are not allowed to mark is not a queue.
 */
class PracticalAdminTest extends TestCase
{
    use RefreshDatabase;

    private function task(): PracticalTask
    {
        return PracticalTask::query()->create([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Calibrate a fuel sensor',
            'brief' => '<p>Calibrate the sensor on the object below.</p>',
            'is_published' => true,
        ]);
    }

    private function submissionBy(User $trainee, ?PracticalTask $task = null): PracticalSubmission
    {
        $task ??= $this->task();

        $draft = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        return app(SubmitPracticalTask::class)->submit($draft, 'Checked the raw value, then the mapping.');
    }

    public function test_the_practical_screens_render_for_an_admin(): void
    {
        $task = $this->task();
        $this->submissionBy($this->trainee(), $task);

        $this->actingAs($this->admin());

        foreach ([
            PracticalTaskResource::getUrl('index'),
            PracticalTaskResource::getUrl('create'),
            PracticalTaskResource::getUrl('edit', ['record' => $task]),
            PracticalSubmissionResource::getUrl('index'),
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    /** The queue is the trainer's own cohort, not everybody's work. */
    public function test_the_marking_queue_is_cohort_scoped(): void
    {
        $task = $this->task();

        $mine = $this->trainee();
        $theirs = $this->trainee();

        $this->submissionBy($mine, $task);
        $this->submissionBy($theirs, $task);

        $trainer = $this->trainer();
        app(AssignTrainee::class)->handle($mine, $trainer, $this->admin());

        $this->actingAs($trainer);

        $visible = PracticalSubmissionResource::getEloquentQuery()->pluck('user_id')->all();

        $this->assertContains($mine->id, $visible);
        $this->assertNotContains($theirs->id, $visible);
    }

    public function test_an_admin_sees_every_submission(): void
    {
        $task = $this->task();

        $first = $this->trainee();
        $second = $this->trainee();

        $this->submissionBy($first, $task);
        $this->submissionBy($second, $task);

        $this->actingAs($this->admin());

        $visible = PracticalSubmissionResource::getEloquentQuery()->pluck('user_id')->all();

        $this->assertContains($first->id, $visible);
        $this->assertContains($second->id, $visible);
    }

    /** Submissions come from trainees doing the work, never from the admin side. */
    public function test_submissions_cannot_be_created_from_the_admin_panel(): void
    {
        $this->actingAs($this->admin());

        $this->assertFalse(PracticalSubmissionResource::canCreate());
    }

    public function test_the_queue_badge_counts_only_work_awaiting_marking(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();

        $trainer = $this->trainer();
        app(AssignTrainee::class)->handle($trainee, $trainer, $this->admin());

        $this->actingAs($trainer);
        $this->assertNull(PracticalSubmissionResource::getNavigationBadge());

        $this->submissionBy($trainee, $task);
        $this->assertSame('1', PracticalSubmissionResource::getNavigationBadge());

        // A draft is not in anybody's queue.
        PracticalSubmission::query()->update(['status' => SubmissionStatus::Draft]);
        $this->assertNull(PracticalSubmissionResource::getNavigationBadge());
    }

    public function test_a_task_attached_to_a_lesson_inherits_that_lessons_course(): void
    {
        $course = Course::factory()->create();
        $module = \App\Models\CourseModule::factory()->for($course)->create();
        $lesson = \App\Models\Lesson::factory()->for($module, 'module')->create();

        $task = PracticalTask::query()->create([
            // Deliberately wrong — the lesson's course must win.
            'course_id' => Course::factory()->create()->id,
            'lesson_id' => $lesson->id,
            'title' => 'Attached task',
            'brief' => '<p>Do the thing.</p>',
        ]);

        $this->assertSame($course->id, $task->fresh()->course_id);
    }
}
