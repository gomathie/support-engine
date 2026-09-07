<?php

namespace Tests\Feature;

use App\Actions\Cohorts\AssignTrainee;
use App\Actions\Practical\SubmitPracticalTask;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\QuestionType;
use App\Filament\Resources\Users\UserResource;
use App\Models\Course;
use App\Models\PracticalTask;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\TrainerAssignmentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reassignment and its audit trail (PA-13).
 *
 * The cohort mechanics are covered in CohortAssignmentTest; this is about what
 * an administrator can see and do, and about the handover being reported
 * honestly — a number that under-counts the inherited queue is worse than no
 * number, because it will be trusted.
 */
class TrainerReassignmentTest extends TestCase
{
    use RefreshDatabase;

    private function traineeWithUngradedWritten(User $trainee): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id]);

        $written = QuizQuestion::factory()->for($quiz)->create([
            'type' => QuestionType::Written,
            'points' => 5,
        ]);

        $attempt = app(StartQuizAttempt::class)->handle($trainee, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'An answer.'],
        ]);
    }

    private function traineeWithUnmarkedPractical(User $trainee): void
    {
        $task = PracticalTask::query()->create([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Calibrate a sensor',
            'brief' => '<p>Do it.</p>',
            'is_published' => true,
        ]);

        $draft = app(SubmitPracticalTask::class)->draftFor($trainee, $task);
        app(SubmitPracticalTask::class)->submit($draft, 'A write-up that is long enough.');
    }

    /**
     * A practical submission awaiting marking is work the incoming trainer
     * picks up, exactly as an ungraded written answer is. Counting only the
     * latter under-reports the handover.
     */
    public function test_the_inherited_queue_counts_practicals_as_well_as_written_answers(): void
    {
        $first = $this->trainer();
        $second = $this->trainer();
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $first, $admin);

        $this->traineeWithUngradedWritten($trainee);
        $this->traineeWithUnmarkedPractical($trainee);

        app(AssignTrainee::class)->handle($trainee, $second, $admin, 'Handover while Aisha is on leave.');

        $log = TrainerAssignmentLog::query()
            ->where('trainee_id', $trainee->id)
            ->where('action', TrainerAssignmentLog::ACTION_REASSIGNED)
            ->sole();

        $this->assertSame(
            2,
            $log->pending_grading_inherited,
            'One written answer plus one practical submission is two pieces of work.',
        );
    }

    public function test_the_log_reads_as_a_handover(): void
    {
        $first = $this->trainer(attributes: ['name' => 'Aisha Bello']);
        $second = $this->trainer(attributes: ['name' => 'Marek Nowak']);
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $first, $admin);
        app(AssignTrainee::class)->handle($trainee, $second, $admin, 'Cohort rebalance.');

        $log = TrainerAssignmentLog::query()->latest('id')->first();

        $this->assertSame('Aisha Bello → Marek Nowak', $log->transition());
        $this->assertSame('Reassigned', $log->actionLabel());
        $this->assertSame('Cohort rebalance.', $log->reason);
        $this->assertSame($admin->id, $log->actor_id);
    }

    public function test_a_first_assignment_reads_as_coming_from_nobody(): void
    {
        $trainer = $this->trainer(attributes: ['name' => 'Aisha Bello']);
        $trainee = $this->trainee();

        app(AssignTrainee::class)->handle($trainee, $trainer, $this->admin());

        $log = TrainerAssignmentLog::query()->sole();

        $this->assertSame('Unassigned → Aisha Bello', $log->transition());
        $this->assertSame('Assigned', $log->actionLabel());
    }

    public function test_unassigning_reads_as_going_to_nobody(): void
    {
        $trainer = $this->trainer(attributes: ['name' => 'Aisha Bello']);
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $trainer, $admin);
        app(AssignTrainee::class)->unassign($trainee, $admin, 'Left the company.');

        $log = TrainerAssignmentLog::query()->latest('id')->first();

        $this->assertSame('Aisha Bello → Unassigned', $log->transition());
        $this->assertSame('Unassigned', $log->actionLabel());
    }

    // ─── AUTHORITY ───────────────────────────────────────────

    /**
     * Only an Admin decides who trains whom. A Trainer who could assign would
     * be able to hand a struggling trainee to somebody else, or take on work
     * they should not have.
     */
    public function test_only_an_admin_holds_the_assignment_permissions(): void
    {
        $admin = $this->admin();
        $trainer = $this->trainer();

        $this->assertTrue($admin->can('trainees.assign'));
        $this->assertTrue($admin->can('trainees.reassign'));

        $this->assertFalse($trainer->can('trainees.assign'));
        $this->assertFalse($trainer->can('trainees.reassign'));
    }

    public function test_the_user_list_shows_the_current_trainer(): void
    {
        $trainer = $this->trainer(attributes: ['name' => 'Aisha Bello']);
        $trainee = $this->trainee(attributes: ['name' => 'Sam Trainee']);

        app(AssignTrainee::class)->handle($trainee, $trainer, $this->admin());

        $this->actingAs($this->admin())
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Aisha Bello');
    }

    public function test_the_current_trainer_is_reachable_from_the_trainee(): void
    {
        $trainer = $this->trainer();
        $trainee = $this->trainee();

        $this->assertNull($trainee->currentTrainer());

        app(AssignTrainee::class)->handle($trainee, $trainer, $this->admin());

        $this->assertSame($trainer->id, $trainee->fresh()->currentTrainer()?->id);
    }
}
