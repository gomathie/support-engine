<?php

namespace Tests\Feature;

use App\Actions\Cohorts\AssignTrainee;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Who trains whom, and what that entitles them to.
 *
 * The distinction these exist to protect: a trainer may READ any transcript
 * but may only GRADE their own cohort. Those used to be one department-shaped
 * rule, and collapsing them again would silently widen who can put a mark on
 * somebody's record.
 */
class CohortAssignmentTest extends TestCase
{
    use RefreshDatabase;

    // ─── ASSIGNMENT ──────────────────────────────────────────

    public function test_an_admin_can_assign_a_trainee_to_a_trainer(): void
    {
        $trainer = $this->trainer();
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $trainer, $admin);

        $this->assertTrue($trainer->trainees()->whereKey($trainee->getKey())->exists());
        $this->assertTrue($trainee->fresh()->currentTrainer()->is($trainer));

        $this->assertDatabaseHas('trainer_assignment_logs', [
            'trainee_id' => $trainee->id,
            'to_trainer_id' => $trainer->id,
            'actor_id' => $admin->id,
            'action' => 'assigned',
        ]);
    }

    public function test_assigning_the_same_trainer_twice_does_not_duplicate_history(): void
    {
        $trainer = $this->trainer();
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $trainer, $admin);
        app(AssignTrainee::class)->handle($trainee, $trainer, $admin);

        $this->assertSame(1, DB::table('trainer_trainee')
            ->where('trainee_id', $trainee->id)->count());
        $this->assertSame(1, DB::table('trainer_assignment_logs')
            ->where('trainee_id', $trainee->id)->count());
    }

    public function test_nobody_can_be_their_own_trainer(): void
    {
        $trainer = $this->trainer();

        $this->expectException(ValidationException::class);

        app(AssignTrainee::class)->handle($trainer, $trainer, $this->admin());
    }

    public function test_a_trainee_cannot_be_assigned_as_a_trainer(): void
    {
        $this->expectException(ValidationException::class);

        app(AssignTrainee::class)->handle(
            $this->trainee(),
            $this->trainee(),
            $this->admin(),
        );
    }

    // ─── REASSIGNMENT ────────────────────────────────────────

    /**
     * The old assignment is closed, not deleted — a completed grade has to stay
     * attributable to whoever held the cohort at the time.
     */
    public function test_reassignment_closes_the_old_row_and_keeps_the_history(): void
    {
        $first = $this->trainer();
        $second = $this->trainer();
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $first, $admin);
        app(AssignTrainee::class)->handle($trainee, $second, $admin, 'First trainer on leave');

        $this->assertTrue($trainee->fresh()->currentTrainer()->is($second));
        $this->assertFalse($first->trainees()->whereKey($trainee->getKey())->exists());

        // The closed row survives.
        $this->assertSame(2, DB::table('trainer_trainee')
            ->where('trainee_id', $trainee->id)->count());
        $this->assertSame(1, DB::table('trainer_trainee')
            ->where('trainee_id', $trainee->id)->whereNotNull('ended_at')->count());

        $this->assertDatabaseHas('trainer_assignment_logs', [
            'trainee_id' => $trainee->id,
            'from_trainer_id' => $first->id,
            'to_trainer_id' => $second->id,
            'action' => 'reassigned',
            'reason' => 'First trainer on leave',
        ]);
    }

    /** Only one trainer at a time — enforced by a partial unique index. */
    public function test_a_trainee_has_at_most_one_active_trainer(): void
    {
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $this->trainer(), $admin);
        app(AssignTrainee::class)->handle($trainee, $this->trainer(), $admin);
        app(AssignTrainee::class)->handle($trainee, $this->trainer(), $admin);

        $this->assertSame(1, DB::table('trainer_trainee')
            ->where('trainee_id', $trainee->id)
            ->whereNull('ended_at')
            ->count());
    }

    /** The new trainer inherits the queue; the log records how much. */
    public function test_pending_grading_moves_with_the_trainee(): void
    {
        $first = $this->trainer();
        $second = $this->trainer();
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $first, $admin);

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

        $this->assertFalse($first->canGrade($trainee) === false);

        app(AssignTrainee::class)->handle($trainee, $second, $admin, 'Handover');

        // The queue follows the cohort.
        $this->assertTrue($second->canGrade($trainee));
        $this->assertFalse($first->fresh()->canGrade($trainee));

        $this->assertDatabaseHas('trainer_assignment_logs', [
            'trainee_id' => $trainee->id,
            'to_trainer_id' => $second->id,
            'pending_grading_inherited' => 1,
        ]);
    }

    public function test_a_trainee_can_be_unassigned(): void
    {
        $trainer = $this->trainer();
        $trainee = $this->trainee();
        $admin = $this->admin();

        app(AssignTrainee::class)->handle($trainee, $trainer, $admin);
        app(AssignTrainee::class)->unassign($trainee, $admin, 'Left the company');

        $this->assertNull($trainee->fresh()->currentTrainer());
        $this->assertDatabaseHas('trainer_assignment_logs', [
            'trainee_id' => $trainee->id,
            'action' => 'unassigned',
        ]);
    }

    // ─── WHAT A COHORT ENTITLES YOU TO ───────────────────────

    public function test_a_trainer_can_grade_only_their_own_cohort(): void
    {
        $trainer = $this->trainer();
        $mine = $this->trainee();
        $theirs = $this->trainee();

        app(AssignTrainee::class)->handle($mine, $trainer, $this->admin());
        app(AssignTrainee::class)->handle($theirs, $this->trainer(), $this->admin());

        $this->assertTrue($trainer->canGrade($mine));
        $this->assertFalse($trainer->canGrade($theirs));
    }

    public function test_an_unassigned_trainer_can_grade_nobody(): void
    {
        $this->assertFalse($this->trainer()->canGrade($this->trainee()));
    }

    public function test_an_admin_can_grade_anybody_but_not_themselves(): void
    {
        $admin = $this->admin();

        $this->assertTrue($admin->canGrade($this->trainee()));
        $this->assertFalse($admin->canGrade($admin));
    }

    public function test_a_trainee_can_grade_nobody(): void
    {
        $this->assertFalse($this->trainee()->canGrade($this->trainee()));
    }

    /**
     * The Phase 0 split: reading a transcript and marking it are now different
     * permissions. A trainer holds the first for everybody and the second only
     * for their cohort.
     */
    public function test_a_trainer_may_read_any_transcript_but_grade_only_their_own(): void
    {
        $trainer = $this->trainer();
        $outsider = $this->trainee();

        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id]);
        QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();

        $attempt = app(StartQuizAttempt::class)->handle($outsider, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, []);

        // transcripts.view-all comes with the Trainer role from RoleSeeder.
        $this->assertTrue($trainer->can('view', $attempt));

        // But marking it is out of bounds — not their cohort.
        $this->assertFalse($trainer->can('grade', $attempt));
    }
}
