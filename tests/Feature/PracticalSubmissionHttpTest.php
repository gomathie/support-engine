<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Practical\GradePracticalSubmission;
use App\Actions\Practical\SubmitPracticalTask;
use App\Enums\RubricCriterion;
use App\Enums\SubmissionStatus;
use App\Models\Course;
use App\Models\PracticalSubmission;
use App\Models\PracticalTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The trainee's side of a practical task over HTTP.
 *
 * The action layer has its own tests; these cover the round trip, which is where
 * authorisation and payload shape actually bite.
 */
class PracticalSubmissionHttpTest extends TestCase
{
    use RefreshDatabase;

    private function task(array $attributes = []): PracticalTask
    {
        return PracticalTask::query()->create([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Calibrate a fuel sensor',
            'brief' => '<p>Calibrate the sensor.</p>',
            'submission_instructions' => 'Attach a screenshot.',
            'is_published' => true,
            ...$attributes,
        ]);
    }

    private function enrolled(PracticalTask $task): User
    {
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $task->course);

        return $user;
    }

    public function test_an_enrolled_trainee_sees_the_task_and_the_rubric_before_starting(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        $this->actingAs($user)
            ->get(route('practical.show', [$task->course->slug, $task->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Practical/Show')
                ->where('task.title', 'Calibrate a fuel sensor')

                // The standard is visible before they start, not only after.
                ->has('rubric', 4)
                ->where('rubric.0.label', 'Correctness')
                ->where('rubric.0.minimum', 3)
                ->where('rubric.0.is_critical', true)
                ->where('passRule.total_needed', RubricCriterion::PASS_TOTAL)

                ->where('submission', null)
                ->where('can.attempt', true));
    }

    public function test_an_unpublished_task_is_not_reachable(): void
    {
        $task = $this->task(['is_published' => false]);
        $user = $this->enrolled($task);

        $this->actingAs($user)
            ->get(route('practical.show', [$task->course->slug, $task->slug]))
            ->assertForbidden();
    }

    /** Reading is one thing; submitting needs the course actually assigned. */
    public function test_someone_not_enrolled_cannot_start_it(): void
    {
        $task = $this->task();
        $outsider = $this->trainee();

        $this->actingAs($outsider)
            ->post(route('practical.start', [$task->course->slug, $task->slug]))
            ->assertForbidden();

        $this->assertSame(0, PracticalSubmission::query()->count());
    }

    public function test_a_trainee_can_start_write_up_and_hand_in(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        $this->actingAs($user)
            ->post(route('practical.start', [$task->course->slug, $task->slug]))
            ->assertRedirect();

        $submission = PracticalSubmission::query()->sole();
        $this->assertSame(SubmissionStatus::Draft, $submission->status);

        $this->actingAs($user)
            ->put(route('practical.draft', [$task->course->slug, $task->slug]), [
                'body' => 'Checked the raw value first.',
            ])
            ->assertRedirect();

        $this->assertSame('Checked the raw value first.', $submission->fresh()->body);

        $this->actingAs($user)
            ->post(route('practical.submit', [$task->course->slug, $task->slug]), [
                'body' => 'Checked the raw value, then the field mapping, then verified the reading.',
            ])
            ->assertRedirect();

        $submission->refresh();

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_a_write_up_that_says_nothing_is_rejected(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        app(SubmitPracticalTask::class)->draftFor($user, $task);

        $this->actingAs($user)
            ->post(route('practical.submit', [$task->course->slug, $task->slug]), ['body' => 'done'])
            ->assertSessionHasErrors('body');

        $this->assertSame(
            SubmissionStatus::Draft,
            PracticalSubmission::query()->sole()->status,
        );
    }

    public function test_a_handed_in_submission_cannot_be_edited_over_http(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        $draft = app(SubmitPracticalTask::class)->draftFor($user, $task);
        app(SubmitPracticalTask::class)->submit($draft, 'My write-up, which is long enough.');

        $this->actingAs($user)
            ->put(route('practical.draft', [$task->course->slug, $task->slug]), [
                'body' => 'Sneaking in a revision after the fact.',
            ])
            ->assertForbidden();
    }

    // ─── EVIDENCE ────────────────────────────────────────────

    public function test_evidence_is_stored_privately_and_reachable_only_through_the_route(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        app(SubmitPracticalTask::class)->draftFor($user, $task);

        $this->actingAs($user)
            ->post(route('practical.attach', [$task->course->slug, $task->slug]), [
                'file' => UploadedFile::fake()->image('sensor-tab.png'),
            ])
            ->assertRedirect();

        $file = PracticalSubmission::query()->sole()->files()->sole();

        $this->assertSame('private', $file->disk);
        $this->assertSame('sensor-tab.png', $file->original_name);
        Storage::disk('private')->assertExists($file->path);

        $response = $this->actingAs($user)->get(route('practical.files.download', $file));

        $response->assertSuccessful();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_another_trainee_cannot_download_the_evidence(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        app(SubmitPracticalTask::class)->draftFor($user, $task);

        $this->actingAs($user)->post(route('practical.attach', [$task->course->slug, $task->slug]), [
            'file' => UploadedFile::fake()->image('sensor-tab.png'),
        ]);

        $file = PracticalSubmission::query()->sole()->files()->sole();

        $this->actingAs($this->trainee())
            ->get(route('practical.files.download', $file))
            ->assertForbidden();
    }

    public function test_an_executable_attachment_is_refused(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        app(SubmitPracticalTask::class)->draftFor($user, $task);

        $this->actingAs($user)
            ->post(route('practical.attach', [$task->course->slug, $task->slug]), [
                'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('file');
    }

    // ─── FEEDBACK RELEASE ────────────────────────────────────

    /**
     * Marks reach the trainee only once the submission has settled. On a
     * double-marked task that means both trainers agreeing — a trainee must not
     * read one marker's provisional view, still less two that contradict.
     */
    public function test_marks_are_withheld_until_the_submission_settles(): void
    {
        $task = $this->task(['requires_second_marker' => true]);
        $user = $this->enrolled($task);

        $draft = app(SubmitPracticalTask::class)->draftFor($user, $task);
        $submission = app(SubmitPracticalTask::class)->submit($draft, 'A write-up that is long enough to pass validation.');

        app(GradePracticalSubmission::class)->handle($submission, $this->admin(), [
            RubricCriterion::Correctness->value => 4,
            RubricCriterion::Method->value => 4,
            RubricCriterion::Verification->value => 4,
            RubricCriterion::Communication->value => 4,
        ]);

        $this->actingAs($user)
            ->get(route('practical.show', [$task->course->slug, $task->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('submission.status', SubmissionStatus::Submitted->value)
                ->where('submission.total_score', null)
                ->has('submission.feedback', 0));
    }

    public function test_settled_marks_reach_the_trainee_with_the_reasons(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        $draft = app(SubmitPracticalTask::class)->draftFor($user, $task);
        $submission = app(SubmitPracticalTask::class)->submit($draft, 'A write-up that is long enough to pass validation.');

        app(GradePracticalSubmission::class)->handle(
            $submission,
            $this->admin(),
            [
                RubricCriterion::Correctness->value => 4,
                RubricCriterion::Method->value => 3,
                RubricCriterion::Verification->value => 1,
                RubricCriterion::Communication->value => 3,
            ],
            comments: [RubricCriterion::Verification->value => 'You claimed it worked but showed no reading.'],
        );

        $this->actingAs($user)
            ->get(route('practical.show', [$task->course->slug, $task->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('submission.status', SubmissionStatus::Graded->value)
                ->where('submission.total_score', 11)

                // 11 clears the total, but Verification at 1 is below its
                // minimum — so this is a fail, and the trainee is told why.
                ->where('submission.passed', false)
                ->has('submission.feedback', 1)
                ->where('submission.feedback.0.scores.2.comment', 'You claimed it worked but showed no reading.')
                ->where('submission.feedback.0.scores.2.meets_standard', false));
    }

    public function test_the_course_page_lists_practical_tasks(): void
    {
        $task = $this->task();
        $user = $this->enrolled($task);

        $this->actingAs($user)
            ->get(route('courses.show', $task->course->slug))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('practical_tasks', 1)
                ->where('practical_tasks.0.title', 'Calibrate a fuel sensor'));
    }
}
