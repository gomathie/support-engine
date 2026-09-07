<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Practical\GradePracticalSubmission;
use App\Actions\Practical\SubmitPracticalTask;
use App\Actions\Progress\CompleteTopic;
use App\Enums\CompletionRequirement;
use App\Enums\ProgressStatus;
use App\Enums\RubricCriterion;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\PracticalTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Completion is not competence.
 *
 * Reading the material is recorded; whether somebody can do the job is decided
 * by an exam and a practical, both marked by a person other than the learner.
 * These tests hold that line — a course must not finish on reading alone.
 */
class CompletionRequiresAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    private function courseWithPractical(): array
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        Topic::factory()->count(2)->for($lesson, 'lesson')->create();

        $task = PracticalTask::query()->create([
            'course_id' => $course->id,
            'title' => 'Configure a fuel sensor',
            'brief' => '<p>Do the work.</p>',
            'is_published' => true,
        ]);

        return [$course->fresh(), $task];
    }

    private function readEverything(User $user, Course $course): void
    {
        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->topics as $topic) {
            app(CompleteTopic::class)->handle($user, $topic);
        }
    }

    private function statusFor(User $user, Course $course): ?ProgressStatus
    {
        return $user->courseProgress()->where('course_id', $course->id)->first()?->status;
    }

    // ─── THE RULE ────────────────────────────────────────────

    /**
     * The gap this closes: a trainee could previously read every topic, be
     * marked complete, collect a certificate and be awarded a competency level
     * having never demonstrated anything.
     */
    public function test_reading_every_lesson_does_not_complete_a_course_with_a_practical(): void
    {
        [$course] = $this->courseWithPractical();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        $this->assertSame(
            ProgressStatus::InProgress,
            $this->statusFor($user, $course),
            'An unpassed practical must keep the course open.',
        );
    }

    public function test_passing_the_practical_completes_the_course(): void
    {
        [$course, $task] = $this->courseWithPractical();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        $draft = app(SubmitPracticalTask::class)->draftFor($user, $task);
        $submission = app(SubmitPracticalTask::class)->submit($draft, 'What I did, at sufficient length.');

        app(GradePracticalSubmission::class)->handle($submission, $this->admin(), [
            RubricCriterion::Correctness->value => 4,
            RubricCriterion::Method->value => 3,
            RubricCriterion::Verification->value => 3,
            RubricCriterion::Communication->value => 3,
        ]);

        $this->assertSame(
            ProgressStatus::Completed,
            $this->statusFor($user->fresh(), $course),
            'Marking the practical must complete the course without another topic tick.',
        );
    }

    /** A failed practical is not a passed one. */
    public function test_failing_the_practical_leaves_the_course_open(): void
    {
        [$course, $task] = $this->courseWithPractical();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        $draft = app(SubmitPracticalTask::class)->draftFor($user, $task);
        $submission = app(SubmitPracticalTask::class)->submit($draft, 'What I did, at sufficient length.');

        app(GradePracticalSubmission::class)->handle(
            $submission,
            $this->admin(),
            [
                RubricCriterion::Correctness->value => 1,
                RubricCriterion::Method->value => 1,
                RubricCriterion::Verification->value => 1,
                RubricCriterion::Communication->value => 1,
            ],
            comments: [
                RubricCriterion::Correctness->value => 'Wrong mapping.',
                RubricCriterion::Method->value => 'Out of order.',
                RubricCriterion::Verification->value => 'Not shown.',
                RubricCriterion::Communication->value => 'Unclear.',
            ],
        );

        $this->assertSame(ProgressStatus::InProgress, $this->statusFor($user->fresh(), $course));
    }

    /** An unpublished practical is not a gate — it is not visible to anybody. */
    public function test_an_unpublished_practical_does_not_block_completion(): void
    {
        [$course, $task] = $this->courseWithPractical();
        $task->update(['is_published' => false]);

        $user = $this->trainee();
        $this->readEverything($user, $course);

        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user, $course));
    }

    /** Courses without practicals behave exactly as before. */
    public function test_a_course_with_no_practical_still_completes_on_its_lessons(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();
        Topic::factory()->count(2)->for($lesson, 'lesson')->create();

        $user = $this->trainee();
        $this->readEverything($user, $course->fresh());

        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user, $course));
    }

    // ─── SELF-ATTESTATION ────────────────────────────────────

    /**
     * The seeded curriculum was entirely self-attested. Nothing in it should be
     * any more — a learner's own tick is not evidence.
     */
    public function test_no_seeded_lesson_is_self_attested(): void
    {
        $this->seed(\Database\Seeders\TrainingContentSeeder::class);

        $selfAttested = Topic::query()
            ->where('completion_requirement', CompletionRequirement::Acknowledge->value)
            ->count();

        $this->assertSame(0, $selfAttested, 'Training content must not rely on the learner ticking a box.');
    }

    public function test_the_enum_distinguishes_assessed_from_attested(): void
    {
        $this->assertTrue(CompletionRequirement::Quiz->isAssessed());
        $this->assertTrue(CompletionRequirement::Task->isAssessed());

        $this->assertFalse(CompletionRequirement::View->isAssessed());
        $this->assertFalse(CompletionRequirement::Acknowledge->isAssessed());

        // Kept for policy sign-off, and the only one the learner decides.
        $this->assertTrue(CompletionRequirement::Acknowledge->isSelfAttested());
        $this->assertFalse(CompletionRequirement::View->isSelfAttested());
    }

    public function test_a_new_lesson_defaults_to_a_reading_record(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        $topic = Topic::query()->create([
            'lesson_id' => $lesson->id,
            'title' => 'A topic with no requirement stated',
        ]);

        $this->assertSame(CompletionRequirement::View, $topic->fresh()->completion_requirement);
    }

    // ─── LESSON NUMBERING ────────────────────────────────────

    /** Progress is measured by assessment, not by a calendar. */
    public function test_the_curriculum_is_numbered_by_lesson_not_dated_by_day(): void
    {
        $this->seed(\Database\Seeders\TrainingContentSeeder::class);

        $dated = \App\Models\Lesson::query()->where('title', 'like', 'Day %')->count();

        $this->assertSame(0, $dated, 'Modules must not be labelled by day.');

        $this->assertSame(
            0,
            Course::query()->where('title', 'like', '%week plan%')
                ->orWhere('title', 'like', '%day plan%')
                ->count(),
            'Course titles must not carry a time cap.',
        );
    }
}
