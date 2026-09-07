<?php

namespace Tests\Feature;

use App\Actions\Practical\SubmitPracticalTask;
use App\Enums\SubmissionStatus;
use App\Models\Course;
use App\Models\PracticalTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Verifiable evidence on practical tasks.
 *
 * Trainees work in a live PILOT account, so a task that says "create an object"
 * leaves a real object behind with a real agent ID. Collecting that identifier
 * is what turns a submission from a claim into something a trainer can check —
 * and Verification is one of the four scored criteria, so it has to be real.
 */
class PracticalEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function task(array $attributes = []): PracticalTask
    {
        return PracticalTask::query()->create([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Create an object for a new client',
            'brief' => '<p>Create it in the training account.</p>',
            'is_published' => true,
            'required_evidence' => [
                ['key' => 'agent_id', 'label' => 'Agent ID (vehicle ID)', 'hint' => 'Object card → Info tab'],
                ['key' => 'contract_id', 'label' => 'Contract ID', 'hint' => null],
            ],
            ...$attributes,
        ]);
    }

    public function test_a_task_declares_the_identifiers_it_wants(): void
    {
        $task = $this->task();

        $this->assertCount(2, $task->evidenceFields());
        $this->assertSame('agent_id', $task->evidenceFields()[0]['key']);
        $this->assertSame('Agent ID (vehicle ID)', $task->evidenceFields()[0]['label']);
        $this->assertTrue($task->requiresEvidence());
    }

    /** A half-filled field list is a configuration mistake, not a requirement. */
    public function test_incomplete_field_definitions_are_ignored(): void
    {
        $task = $this->task([
            'required_evidence' => [
                ['key' => 'agent_id', 'label' => 'Agent ID'],
                ['key' => '', 'label' => 'Nameless'],
                ['label' => 'No key at all'],
            ],
        ]);

        $this->assertCount(1, $task->evidenceFields());
    }

    public function test_a_submission_records_the_identifiers(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();

        $draft = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        $submission = app(SubmitPracticalTask::class)->submit(
            $draft,
            'Created the object against the training contract.',
            ['agent_id' => '884213', 'contract_id' => 'C-4471'],
        );

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertSame('884213', $submission->evidence['agent_id']);
        $this->assertSame('C-4471', $submission->evidence['contract_id']);
    }

    /**
     * The whole point is that a marker can go and look it up. A submission
     * without the identifier cannot be verified, so it cannot be handed in.
     */
    public function test_a_missing_identifier_blocks_the_hand_in(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();

        $draft = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        try {
            app(SubmitPracticalTask::class)->submit(
                $draft,
                'I created the object, trust me.',
                ['agent_id' => '884213'],
            );

            $this->fail('A submission missing a required identifier should be refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Contract ID', $e->errors()['evidence'][0]);
        }

        $this->assertSame(SubmissionStatus::Draft, $draft->fresh()->status);
    }

    public function test_a_blank_identifier_counts_as_missing(): void
    {
        $task = $this->task();
        $draft = app(SubmitPracticalTask::class)->draftFor($this->trainee(), $task);

        $this->expectException(ValidationException::class);

        app(SubmitPracticalTask::class)->submit(
            $draft,
            'A write-up long enough to pass.',
            ['agent_id' => '884213', 'contract_id' => '   '],
        );
    }

    // ─── SCREENSHOTS ─────────────────────────────────────────

    public function test_a_task_needing_a_screenshot_refuses_an_empty_hand_in(): void
    {
        $task = $this->task(['requires_screenshot' => true, 'required_evidence' => []]);
        $draft = app(SubmitPracticalTask::class)->draftFor($this->trainee(), $task);

        try {
            app(SubmitPracticalTask::class)->submit($draft, 'I did the work and it looked right.');

            $this->fail('A task requiring a screenshot should refuse a submission with nothing attached.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('screenshot', $e->errors()['evidence'][0]);
        }
    }

    public function test_an_attached_screenshot_satisfies_the_requirement(): void
    {
        $task = $this->task(['requires_screenshot' => true, 'required_evidence' => []]);
        $trainee = $this->trainee();

        $draft = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        Storage::disk('private')->put('practical-evidence/shot.png', 'bytes');

        $draft->files()->create([
            'disk' => 'private',
            'path' => 'practical-evidence/shot.png',
            'original_name' => 'sensor-tab.png',
            'mime_type' => 'image/png',
            'size_bytes' => 5,
        ]);

        $submission = app(SubmitPracticalTask::class)->submit($draft->fresh(), 'What I did, at length.');

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
    }

    /** A task asking for nothing extra behaves as it always did. */
    public function test_a_task_with_no_evidence_requirements_hands_in_normally(): void
    {
        $task = $this->task(['required_evidence' => [], 'requires_screenshot' => false]);
        $draft = app(SubmitPracticalTask::class)->draftFor($this->trainee(), $task);

        $submission = app(SubmitPracticalTask::class)->submit($draft, 'A write-up long enough to pass.');

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertFalse($task->requiresEvidence());
    }

    // ─── OVER HTTP ───────────────────────────────────────────

    public function test_the_trainee_page_carries_the_fields_to_fill_in(): void
    {
        $task = $this->task(['requires_screenshot' => true]);
        $trainee = $this->trainee();

        app(\App\Actions\Enrollment\EnrollEmployee::class)->handle($trainee, $task->course);

        $this->actingAs($trainee)
            ->get(route('practical.show', [$task->course->slug, $task->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('task.evidence_fields', 2)
                ->where('task.evidence_fields.0.label', 'Agent ID (vehicle ID)')
                ->where('task.evidence_fields.0.hint', 'Object card → Info tab')
                ->where('task.requires_screenshot', true));
    }

    public function test_submitting_over_http_stores_the_identifiers(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();

        app(\App\Actions\Enrollment\EnrollEmployee::class)->handle($trainee, $task->course);
        app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        $this->actingAs($trainee)
            ->post(route('practical.submit', [$task->course->slug, $task->slug]), [
                'body' => 'Created the object against the training contract and verified it.',
                'evidence' => ['agent_id' => '884213', 'contract_id' => 'C-4471'],
            ])
            ->assertRedirect();

        $submission = $task->submissions()->sole();

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertSame('884213', $submission->evidence['agent_id']);
    }

    public function test_submitting_over_http_without_the_identifiers_is_refused(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();

        app(\App\Actions\Enrollment\EnrollEmployee::class)->handle($trainee, $task->course);
        app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        $this->actingAs($trainee)
            ->post(route('practical.submit', [$task->course->slug, $task->slug]), [
                'body' => 'A write-up long enough to pass validation on its own.',
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertSame(SubmissionStatus::Draft, $task->submissions()->sole()->status);
    }
}
