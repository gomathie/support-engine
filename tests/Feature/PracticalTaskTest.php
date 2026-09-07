<?php

namespace Tests\Feature;

use App\Actions\Cohorts\AssignTrainee;
use App\Actions\Practical\GradePracticalSubmission;
use App\Actions\Practical\SubmitPracticalTask;
use App\Enums\RubricCriterion;
use App\Enums\SubmissionStatus;
use App\Models\Course;
use App\Models\PracticalGrading;
use App\Models\PracticalSubmission;
use App\Models\PracticalTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Practical tasks and the four-criterion rubric (PA-14).
 *
 * The rubric rules from §4.3 are the substance here: the threshold is not a
 * simple total, a low score must be explained, and two markers who disagree do
 * not get averaged into a false consensus.
 */
class PracticalTaskTest extends TestCase
{
    use RefreshDatabase;

    private function task(array $attributes = []): PracticalTask
    {
        $course = Course::factory()->create();

        return PracticalTask::query()->create([
            'course_id' => $course->id,
            'title' => 'Configure a fuel sensor from raw data',
            'brief' => '<p>Take the object below and calibrate its fuel sensor.</p>',
            'submission_instructions' => 'Attach a screenshot of the sensor tab and explain your reasoning.',
            'is_published' => true,
            ...$attributes,
        ]);
    }

    /** @return array<string, int> */
    private function scores(int $correctness, int $method, int $verification, int $communication): array
    {
        return [
            RubricCriterion::Correctness->value => $correctness,
            RubricCriterion::Method->value => $method,
            RubricCriterion::Verification->value => $verification,
            RubricCriterion::Communication->value => $communication,
        ];
    }

    private function submittedWork(PracticalTask $task, ?User $trainee = null): PracticalSubmission
    {
        $trainee ??= $this->trainee();

        $submission = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        return app(SubmitPracticalTask::class)->submit($submission, 'I checked the raw value first, then the mapping.');
    }

    // ─── THE THRESHOLD ───────────────────────────────────────

    /**
     * The rule is not "10 or more". 4/4/2/0 totals 10 and still fails, because
     * somebody who cannot explain what they did has not met the standard.
     */
    public function test_the_total_alone_does_not_decide_a_pass(): void
    {
        $onTotalButMute = $this->scores(4, 4, 2, 0);

        $this->assertSame(10, PracticalGrading::total($onTotalButMute));
        $this->assertFalse(
            PracticalGrading::passes($onTotalButMute),
            'A zero on Communication must fail regardless of the total.',
        );
    }

    /** Correctness carries a higher bar: tidily reaching the wrong answer is still wrong. */
    public function test_correctness_below_three_fails_however_good_the_rest(): void
    {
        $this->assertFalse(PracticalGrading::passes($this->scores(2, 4, 4, 4)));
        $this->assertTrue(PracticalGrading::passes($this->scores(3, 4, 4, 4)));
    }

    public function test_a_bare_pass_is_recognised(): void
    {
        // 3/3/2/2 = 10, every minimum met.
        $this->assertTrue(PracticalGrading::passes($this->scores(3, 3, 2, 2)));

        // 3/2/2/2 = 9 — every minimum met, but short on the total.
        $this->assertFalse(PracticalGrading::passes($this->scores(3, 2, 2, 2)));
    }

    // ─── MARKING ─────────────────────────────────────────────

    public function test_marking_records_every_criterion_with_its_descriptor(): void
    {
        $task = $this->task();
        $submission = $this->submittedWork($task);

        $grading = app(GradePracticalSubmission::class)->handle(
            submission: $submission,
            grader: $this->admin(),
            scores: $this->scores(4, 3, 3, 3),
            summary: 'Solid work, verified properly.',
        );

        $this->assertSame(13, $grading->total_score);
        $this->assertTrue($grading->passed);
        $this->assertCount(4, $grading->scores);

        $this->assertSame(
            'Correct, with edge cases handled',
            $grading->scores->firstWhere('criterion', RubricCriterion::Correctness)->descriptor(),
        );

        $submission->refresh();
        $this->assertSame(SubmissionStatus::Graded, $submission->status);
        $this->assertTrue($submission->passed);
        $this->assertSame(13, $submission->total_score);
    }

    /** A bare 1 teaches nothing — the grader has to say why. */
    public function test_a_low_score_without_a_comment_is_rejected(): void
    {
        $task = $this->task();
        $submission = $this->submittedWork($task);

        try {
            app(GradePracticalSubmission::class)->handle(
                submission: $submission,
                grader: $this->admin(),
                scores: $this->scores(4, 4, 1, 4),
            );

            $this->fail('A score of 1 with no comment should have been rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey(
                'comments.'.RubricCriterion::Verification->value,
                $e->errors(),
            );
        }

        $this->assertSame(0, PracticalGrading::query()->count());
    }

    public function test_a_low_score_with_a_comment_is_accepted(): void
    {
        $task = $this->task();
        $submission = $this->submittedWork($task);

        $grading = app(GradePracticalSubmission::class)->handle(
            submission: $submission,
            grader: $this->admin(),
            scores: $this->scores(4, 4, 1, 4),
            comments: [RubricCriterion::Verification->value => 'You claimed it worked but showed no reading.'],
        );

        $this->assertFalse($grading->passed);
        $this->assertStringContainsString('Verification', $grading->verdictExplanation());
    }

    public function test_every_criterion_must_be_scored(): void
    {
        $task = $this->task();
        $submission = $this->submittedWork($task);

        $this->expectException(ValidationException::class);

        app(GradePracticalSubmission::class)->handle(
            submission: $submission,
            grader: $this->admin(),
            scores: [RubricCriterion::Correctness->value => 4],
        );
    }

    /** A grader revising their own marks is not a second opinion. */
    public function test_regrading_updates_the_same_grading(): void
    {
        $task = $this->task();
        $submission = $this->submittedWork($task);
        $grader = $this->admin();

        app(GradePracticalSubmission::class)->handle($submission, $grader, $this->scores(4, 4, 4, 4));
        app(GradePracticalSubmission::class)->handle($submission, $grader, $this->scores(3, 3, 3, 3));

        $this->assertSame(1, PracticalGrading::query()->count());
        $this->assertSame(12, $submission->fresh()->total_score);
    }

    // ─── CALIBRATION ─────────────────────────────────────────

    /**
     * §4.3: the first submissions are marked independently by two trainers.
     * One set of marks is an opinion, not a result.
     */
    public function test_a_double_marked_task_does_not_settle_on_one_grading(): void
    {
        $task = $this->task(['requires_second_marker' => true]);
        $submission = $this->submittedWork($task);

        app(GradePracticalSubmission::class)->handle($submission, $this->admin(), $this->scores(4, 4, 4, 4));

        $submission->refresh();

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertNull($submission->passed);
        $this->assertFalse($submission->hasEnoughGradings());
    }

    public function test_two_markers_who_agree_settle_the_submission(): void
    {
        $task = $this->task(['requires_second_marker' => true]);
        $submission = $this->submittedWork($task);

        app(GradePracticalSubmission::class)->handle($submission, $this->admin(), $this->scores(4, 4, 4, 4));
        app(GradePracticalSubmission::class)->handle($submission, $this->admin(), $this->scores(3, 3, 4, 4));

        $submission->refresh();

        $this->assertSame(SubmissionStatus::Graded, $submission->status);
        $this->assertTrue($submission->passed);

        // 16 and 14 — the mean, since the verdict is already agreed.
        $this->assertSame(15, $submission->total_score);
    }

    /**
     * The disagreement is the signal the calibration exists to surface.
     * Averaging one trainer's pass with another's fail would hide it.
     */
    public function test_two_markers_who_disagree_leave_it_unsettled(): void
    {
        $task = $this->task(['requires_second_marker' => true]);
        $submission = $this->submittedWork($task);

        app(GradePracticalSubmission::class)->handle($submission, $this->admin(), $this->scores(4, 4, 4, 4));
        app(GradePracticalSubmission::class)->handle(
            $submission,
            $this->admin(),
            $this->scores(2, 2, 2, 2),
            comments: [
                RubricCriterion::Correctness->value => 'Wrong mapping.',
                RubricCriterion::Method->value => 'Out of order.',
                RubricCriterion::Verification->value => 'Not shown.',
                RubricCriterion::Communication->value => 'Unclear.',
            ],
        );

        $submission->refresh();

        $this->assertTrue($submission->hasEnoughGradings());
        $this->assertTrue($submission->markersDisagree());
        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertNull($submission->passed, 'A split verdict must not be averaged into a result.');
        $this->assertSame(8, $submission->scoreSpread());
    }

    // ─── SUBMISSION LIFECYCLE ────────────────────────────────

    public function test_a_draft_is_reused_rather_than_duplicated(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();

        $first = app(SubmitPracticalTask::class)->draftFor($trainee, $task);
        $second = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PracticalSubmission::query()->count());
    }

    public function test_a_handed_in_submission_cannot_be_edited(): void
    {
        $task = $this->task();
        $submission = $this->submittedWork($task);

        $this->assertFalse($submission->isEditable());
    }

    /**
     * Returning is not failing. "You forgot the screenshot" is a gap in the
     * evidence, and counting it as a fail would distort the retry statistics.
     */
    public function test_returning_for_revision_reopens_it_without_a_fail(): void
    {
        $task = $this->task();
        $submission = $this->submittedWork($task);

        app(GradePracticalSubmission::class)->returnForRevision(
            $submission,
            $this->admin(),
            'No screenshot of the sensor tab — add it and resubmit.',
        );

        $submission->refresh();

        $this->assertSame(SubmissionStatus::Returned, $submission->status);
        $this->assertNull($submission->passed);
        $this->assertTrue($submission->isEditable());

        // Same attempt, revised — not a second attempt.
        $this->assertSame(1, $submission->attempt_number);

        $resubmitted = app(SubmitPracticalTask::class)->submit($submission, 'Screenshot added.');

        $this->assertSame(SubmissionStatus::Submitted, $resubmitted->status);
        $this->assertNull($resubmitted->returned_reason);
    }

    public function test_an_empty_submission_is_refused(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();

        $submission = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        try {
            app(SubmitPracticalTask::class)->submit($submission);

            $this->fail('An empty submission should not be accepted.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertSame(SubmissionStatus::Draft, $submission->fresh()->status);
    }

    // ─── AUTHORITY ───────────────────────────────────────────

    public function test_marking_is_cohort_scoped_and_reading_is_not(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();
        $submission = $this->submittedWork($task, $trainee);

        $ownTrainer = $this->trainer();
        $otherTrainer = $this->trainer();

        app(AssignTrainee::class)->handle($trainee, $ownTrainer, $this->admin());

        $this->assertTrue($ownTrainer->can('grade', $submission));

        $this->assertTrue($otherTrainer->can('view', $submission));
        $this->assertFalse($otherTrainer->can('grade', $submission));
    }

    public function test_nobody_marks_their_own_practical(): void
    {
        $task = $this->task();
        $admin = $this->admin();
        $submission = $this->submittedWork($task, $admin);

        $this->assertFalse($admin->can('grade', $submission));
    }

    public function test_a_trainee_sees_only_their_own_submission(): void
    {
        $task = $this->task();
        $trainee = $this->trainee();
        $submission = $this->submittedWork($task, $trainee);

        $this->assertTrue($trainee->can('view', $submission));
        $this->assertFalse($this->trainee()->can('view', $submission));
    }

    public function test_the_rubric_exposes_its_descriptors(): void
    {
        $this->assertSame(16, RubricCriterion::maxTotal());
        $this->assertSame(3, RubricCriterion::Correctness->minimumToPass());
        $this->assertSame(2, RubricCriterion::Communication->minimumToPass());
        $this->assertTrue(RubricCriterion::Correctness->isCritical());

        $this->assertSame('Verified plus a negative check', RubricCriterion::Verification->descriptor(4));
        $this->assertSame('None', RubricCriterion::Verification->descriptor(0));
        $this->assertCount(5, RubricCriterion::Method->descriptors());
    }
}
