<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\GradeWrittenAnswer;
use App\Actions\Quiz\OverrideAttemptResult;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\ProgressStatus;
use App\Enums\QuestionType;
use App\Models\CompetencyArea;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\GradeOverrideLog;
use App\Models\Topic;
use App\Models\Level;
use App\Models\LevelRequirement;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TraineeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Pass/fail override (PA-12).
 *
 * Promotion is automatic; this is the exception path, and the properties that
 * matter are that it is always explained, always reconstructable, and never
 * silently undone by a later re-grade.
 */
class GradeOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** A course with one written-answer exam, sat and failed. */
    private function failedAttempt(?User $trainee = null): QuizAttempt
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();
        Topic::factory()->for($lesson, 'lesson')->create();

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
        ]);

        $question = QuizQuestion::factory()->for($quiz)->create([
            'type' => QuestionType::Written,
            'points' => 10,
        ]);

        $trainee ??= $this->trainee();
        app(EnrollEmployee::class)->handle($trainee, $course);

        $attempt = app(StartQuizAttempt::class)->handle($trainee, $quiz);

        // Through the real submission path, so the answer row is built the way
        // the application builds it.
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [], 'text' => 'A thin answer.'],
        ]);

        // Marked 3/10 — a fail against the 70% pass mark.
        foreach ($attempt->fresh()->answers as $answer) {
            app(GradeWrittenAnswer::class)->handle(
                answer: $answer,
                grader: $this->admin(),
                points: 3,
                feedback: 'Missed the calibration step.',
            );
        }

        return $attempt->fresh();
    }

    public function test_an_override_turns_a_fail_into_a_pass_and_records_why(): void
    {
        $attempt = $this->failedAttempt();
        $actor = $this->admin();

        $this->assertFalse($attempt->passed);

        $attempt = app(OverrideAttemptResult::class)->handle(
            $attempt,
            $actor,
            passed: true,
            reason: 'Question 1 was ambiguous; upheld on appeal.',
        );

        $this->assertTrue($attempt->passed);
        $this->assertTrue($attempt->override_passed);
        $this->assertSame($actor->id, $attempt->overridden_by);
        $this->assertNotNull($attempt->overridden_at);

        $log = GradeOverrideLog::query()->sole();

        $this->assertSame(GradeOverrideLog::ACTION_OVERRIDDEN, $log->action);
        $this->assertFalse($log->from_passed);
        $this->assertTrue($log->to_passed);
        $this->assertSame('Question 1 was ambiguous; upheld on appeal.', $log->reason);
        $this->assertSame('Fail → Pass', $log->transition());
    }

    /**
     * "Scored 30% but passed on appeal" is the truth worth keeping. Rewriting
     * the score to the pass mark would erase the appeal.
     */
    public function test_an_override_does_not_rewrite_the_score(): void
    {
        $attempt = $this->failedAttempt();
        $scoreBefore = $attempt->score;

        $attempt = app(OverrideAttemptResult::class)->handle(
            $attempt,
            $this->admin(),
            passed: true,
            reason: 'Upheld on appeal.',
        );

        $this->assertSame($scoreBefore, $attempt->score);
        $this->assertTrue($attempt->passed);
    }

    /**
     * The reason this is stored rather than written straight into `passed`:
     * re-marking a written answer re-runs finalisation, and a human decision
     * must not evaporate when it does.
     */
    public function test_a_later_regrade_does_not_silently_undo_the_override(): void
    {
        $attempt = $this->failedAttempt();

        app(OverrideAttemptResult::class)->handle(
            $attempt,
            $this->admin(),
            passed: true,
            reason: 'Upheld on appeal.',
        );

        // An examiner revisits the same answer and confirms the low mark.
        foreach ($attempt->fresh()->answers as $answer) {
            app(GradeWrittenAnswer::class)->handle(
                answer: $answer,
                grader: $this->admin(),
                points: 4,
                feedback: 'Still short of the standard.',
            );
        }

        $attempt->refresh();

        $this->assertTrue($attempt->passed, 'The override must survive re-finalisation.');
        $this->assertTrue($attempt->override_passed);
    }

    public function test_withdrawing_an_override_restores_the_marked_result(): void
    {
        $attempt = $this->failedAttempt();
        $actor = $this->admin();

        app(OverrideAttemptResult::class)->handle($attempt, $actor, true, 'Upheld on appeal.');

        $attempt = app(OverrideAttemptResult::class)->withdraw(
            $attempt->fresh(),
            $actor,
            reason: 'Appeal panel reversed its finding.',
        );

        $this->assertFalse($attempt->passed);
        $this->assertNull($attempt->override_passed);
        $this->assertNull($attempt->overridden_by);

        $this->assertSame(2, GradeOverrideLog::query()->count());

        $withdrawal = GradeOverrideLog::query()
            ->where('action', GradeOverrideLog::ACTION_WITHDRAWN)
            ->sole();

        $this->assertSame('Pass → Fail', $withdrawal->transition());
    }

    /** Overturning a pass must not leave the level that pass earned standing. */
    public function test_overturning_a_pass_revokes_the_level_it_evidenced(): void
    {
        $attempt = $this->failedAttempt();
        $trainee = $attempt->user;

        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();

        LevelRequirement::query()->create([
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
            'course_id' => $attempt->course_id,
        ]);

        // The award, with this attempt as its stated evidence.
        $award = TraineeLevel::query()->create([
            'user_id' => $trainee->id,
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
            'awarded_at' => now(),
            'quiz_attempt_id' => $attempt->id,
        ]);

        // Bring it to a pass first, so the override is pass → fail.
        app(OverrideAttemptResult::class)->handle($attempt, $this->admin(), true, 'Provisional pass.');

        app(OverrideAttemptResult::class)->handle(
            $attempt->fresh(),
            $this->admin(),
            passed: false,
            reason: 'Provisional pass withdrawn — answer key was wrong.',
        );

        $award->refresh();

        $this->assertNotNull($award->revoked_at);
        $this->assertStringContainsString('answer key was wrong', $award->revoked_reason);
        $this->assertFalse($trainee->fresh()->holdsLevel($level, $area));

        $log = GradeOverrideLog::query()->latest('id')->first();
        $this->assertSame(1, $log->levels_revoked);
    }

    /** Narrow on purpose: a level earned by another route is untouched. */
    public function test_it_does_not_revoke_levels_this_attempt_did_not_evidence(): void
    {
        $attempt = $this->failedAttempt();
        $trainee = $attempt->user;

        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();

        $unrelated = TraineeLevel::query()->create([
            'user_id' => $trainee->id,
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
            'awarded_at' => now(),

            // Earned elsewhere.
            'quiz_attempt_id' => null,
        ]);

        app(OverrideAttemptResult::class)->handle($attempt, $this->admin(), true, 'Provisional.');
        app(OverrideAttemptResult::class)->handle($attempt->fresh(), $this->admin(), false, 'Reversed.');

        $this->assertNull($unrelated->fresh()->revoked_at);
    }

    /** An override that makes a course complete must issue what follows. */
    public function test_an_override_to_pass_updates_course_progress(): void
    {
        $attempt = $this->failedAttempt();
        $trainee = $attempt->user;

        // Finish the topics so only the exam stands between them and done.
        foreach ($attempt->course->topics as $topic) {
            app(\App\Actions\Progress\CompleteTopic::class)->handle($trainee, $topic);
        }

        $this->assertNotSame(
            ProgressStatus::Completed,
            $trainee->courseProgress()->where('course_id', $attempt->course_id)->first()?->status,
        );

        app(OverrideAttemptResult::class)->handle($attempt, $this->admin(), true, 'Upheld on appeal.');

        $this->assertSame(
            ProgressStatus::Completed,
            $trainee->courseProgress()->where('course_id', $attempt->course_id)->first()->status,
        );
    }

    // ------------------------------------------------------------ authority

    /**
     * Overriding is grading with a bigger hammer, so it follows the same
     * boundary: a trainer's own cohort only, and never their own paper.
     */
    public function test_only_someone_who_may_grade_the_trainee_may_override(): void
    {
        $trainee = $this->trainee();
        $attempt = $this->failedAttempt($trainee);

        $ownTrainer = $this->trainer();
        $otherTrainer = $this->trainer();

        app(\App\Actions\Cohorts\AssignTrainee::class)
            ->handle($trainee, $ownTrainer, $this->admin());

        $this->assertTrue($ownTrainer->can('override', $attempt));

        // May read every transcript, but may not overturn outside their cohort.
        $this->assertTrue($otherTrainer->can('view', $attempt));
        $this->assertFalse($otherTrainer->can('override', $attempt));

        $this->assertFalse($this->trainee()->can('override', $attempt));
    }

    public function test_nobody_overrides_their_own_paper(): void
    {
        $admin = $this->admin();
        $attempt = $this->failedAttempt($admin);

        $this->assertFalse($admin->can('override', $attempt));
    }

    /** The capability is a named permission, not an implied tier. */
    public function test_overriding_requires_the_grades_override_permission(): void
    {
        $trainee = $this->trainee();
        $attempt = $this->failedAttempt($trainee);

        $trainer = $this->trainer();
        app(\App\Actions\Cohorts\AssignTrainee::class)
            ->handle($trainee, $trainer, $this->admin());

        $this->assertTrue($trainer->can('override', $attempt));

        // Taken from the role, which is where it lives.
        $trainer->roles->first()->revokePermissionTo('grades.override');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse($trainer->fresh()->can('override', $attempt));
    }
}
