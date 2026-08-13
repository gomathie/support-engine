<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\GradeWrittenAnswer;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Department;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Written answers are marked by a person, so an attempt containing one is not
 * scored on submission — it waits. These cover that hand-off in both
 * directions: what the employee sees while waiting, and what the examiner can
 * and cannot do.
 */
class ManualGradingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Course, 1: Quiz, 2: \App\Models\User} */
    private function scenario(?Department $department = null): array
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id, 'passing_score' => 50]);

        $user = $this->employee($department);
        app(EnrollEmployee::class)->handle($user, $course);

        return [$course, $quiz, $user];
    }

    private function writtenQuestion(Quiz $quiz, int $points = 5): QuizQuestion
    {
        return QuizQuestion::factory()->for($quiz)->create([
            'type' => QuestionType::Written,
            'points' => $points,
            'marking_guidance' => 'Look for the sequence and the verification step.',
        ]);
    }

    // ─── SUBMISSION ──────────────────────────────────────────

    public function test_an_attempt_with_a_written_answer_waits_for_review(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $mcq = QuizQuestion::factory()->for($quiz)->withOptions(4, [0])->create(['points' => 1]);
        $written = $this->writtenQuestion($quiz);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            [
                'question_id' => $mcq->id,
                'option_ids' => [$mcq->options()->where('is_correct', true)->first()->id],
                'text' => null,
            ],
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'My answer.'],
        ]);

        $attempt->refresh();

        $this->assertSame(AttemptStatus::PendingReview, $attempt->status);

        // Deliberately no score: the objective half alone reads as a final mark.
        $this->assertNull($attempt->score);
        $this->assertNull($attempt->passed);

        // The objective half is settled and kept.
        $this->assertSame(1, $attempt->auto_points_earned);
        $this->assertSame(6, $attempt->points_possible);
        $this->assertSame(5, $attempt->manual_points_possible);
    }

    public function test_a_purely_objective_attempt_still_scores_immediately(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $mcq = QuizQuestion::factory()->for($quiz)->withOptions(4, [0])->create();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            [
                'question_id' => $mcq->id,
                'option_ids' => [$mcq->options()->where('is_correct', true)->first()->id],
                'text' => null,
            ],
        ]);

        $attempt->refresh();

        $this->assertSame(AttemptStatus::Completed, $attempt->status);
        $this->assertSame('100.00', $attempt->score);
        $this->assertTrue($attempt->passed);
    }

    // ─── MARKING ─────────────────────────────────────────────

    public function test_marking_the_last_answer_finalises_the_attempt(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $written = $this->writtenQuestion($quiz, 10);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'A good answer.'],
        ]);

        $this->assertTrue($attempt->fresh()->awaitsReview());

        $answer = $attempt->fresh()->answers()->first();

        app(GradeWrittenAnswer::class)->handle(
            answer: $answer,
            grader: $this->admin(),
            points: 8,
            feedback: 'Clear sequence, missed the verification step.',
        );

        $attempt->refresh();

        $this->assertSame(AttemptStatus::Completed, $attempt->status);
        $this->assertSame('80.00', $attempt->score);
        $this->assertTrue($attempt->passed);
        $this->assertNotNull($attempt->reviewed_at);
        $this->assertSame('Clear sequence, missed the verification step.', $answer->fresh()->grader_feedback);
    }

    public function test_the_attempt_stays_in_review_until_every_answer_is_marked(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $one = $this->writtenQuestion($quiz);
        $two = $this->writtenQuestion($quiz);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $one->id, 'option_ids' => [], 'text' => 'One.'],
            ['question_id' => $two->id, 'option_ids' => [], 'text' => 'Two.'],
        ]);

        $grader = $this->admin();
        $answers = $attempt->fresh()->answers()->orderBy('id')->get();

        app(GradeWrittenAnswer::class)->handle($answers[0], $grader, 5);

        $this->assertTrue($attempt->fresh()->awaitsReview());
        $this->assertSame(1, $attempt->fresh()->ungradedAnswers()->count());

        app(GradeWrittenAnswer::class)->handle($answers[1], $grader, 5);

        $this->assertSame(AttemptStatus::Completed, $attempt->fresh()->status);
        $this->assertSame('100.00', $attempt->fresh()->score);
    }

    public function test_marks_outside_the_question_maximum_are_rejected(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $written = $this->writtenQuestion($quiz, 5);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'Answer.'],
        ]);

        $answer = $attempt->fresh()->answers()->first();

        $this->expectException(ValidationException::class);

        app(GradeWrittenAnswer::class)->handle($answer, $this->admin(), 6);
    }

    /** Zero is a legitimate mark, and must not read as "not yet marked". */
    public function test_awarding_zero_still_counts_as_marked(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $written = $this->writtenQuestion($quiz);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'Wrong.'],
        ]);

        $answer = $attempt->fresh()->answers()->first();
        app(GradeWrittenAnswer::class)->handle($answer, $this->admin(), 0);

        $this->assertNotNull($answer->fresh()->graded_at);
        $this->assertFalse($answer->fresh()->awaitsGrading());
        $this->assertSame(AttemptStatus::Completed, $attempt->fresh()->status);
        $this->assertFalse($attempt->fresh()->passed);
    }

    // ─── AUTHORIZATION ───────────────────────────────────────

    /** Nobody marks their own paper — not even an administrator. */
    public function test_nobody_can_grade_their_own_attempt(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id]);

        app(EnrollEmployee::class)->handle($admin, $course);

        $attempt = QuizAttempt::query()->create([
            'user_id' => $admin->id,
            'quiz_id' => $quiz->id,
            'course_id' => $course->id,
            'attempt_number' => 1,
            'status' => AttemptStatus::PendingReview,
            'started_at' => now(),
        ]);

        $this->assertFalse($admin->can('grade', $attempt));
    }

    public function test_a_manager_can_only_grade_their_own_departments(): void
    {
        $mine = Department::factory()->create();
        $theirs = Department::factory()->create();

        $manager = $this->manager($mine);

        [, , $inside] = $this->scenario($mine);
        [, , $outside] = $this->scenario($theirs);

        $insideAttempt = QuizAttempt::query()->create([
            'user_id' => $inside->id,
            'quiz_id' => Quiz::factory()->create()->id,
            'course_id' => Course::factory()->create()->id,
            'attempt_number' => 1,
            'status' => AttemptStatus::PendingReview,
            'started_at' => now(),
        ]);

        $outsideAttempt = QuizAttempt::query()->create([
            'user_id' => $outside->id,
            'quiz_id' => Quiz::factory()->create()->id,
            'course_id' => Course::factory()->create()->id,
            'attempt_number' => 1,
            'status' => AttemptStatus::PendingReview,
            'started_at' => now(),
        ]);

        $this->assertTrue($manager->can('grade', $insideAttempt));
        $this->assertFalse($manager->can('grade', $outsideAttempt));
    }

    public function test_an_employee_cannot_grade_anything(): void
    {
        [$course, $quiz, $owner] = $this->scenario();

        $attempt = QuizAttempt::query()->create([
            'user_id' => $owner->id,
            'quiz_id' => $quiz->id,
            'course_id' => $course->id,
            'attempt_number' => 1,
            'status' => AttemptStatus::PendingReview,
            'started_at' => now(),
        ]);

        $this->assertFalse($this->employee()->can('grade', $attempt));
    }

    // ─── EMPLOYEE VIEW ───────────────────────────────────────

    public function test_the_results_screen_shows_the_waiting_state_and_no_score(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $written = $this->writtenQuestion($quiz);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'My answer.'],
        ]);

        $this->actingAs($user)
            ->get(route('attempts.show', $attempt))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Quizzes/Result')
                ->where('attempt.awaiting_review', true)
                ->where('attempt.score', null)
                ->where('attempt.outstanding', 1)
            );
    }

    /** The rubric is the examiner's, and must never reach the employee. */
    public function test_marking_guidance_is_not_sent_to_the_employee(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $written = $this->writtenQuestion($quiz);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'My answer.'],
        ]);

        $response = $this->actingAs($user)->get(route('attempts.show', $attempt));

        $response->assertDontSee('marking_guidance');
        $response->assertDontSee('Look for the sequence and the verification step.');
    }

    public function test_grader_feedback_reaches_the_employee(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $written = $this->writtenQuestion($quiz);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $written->id, 'option_ids' => [], 'text' => 'My answer.'],
        ]);

        app(GradeWrittenAnswer::class)->handle(
            $attempt->fresh()->answers()->first(),
            $this->admin(),
            4,
            'Good, but say how you would verify it.',
        );

        $this->actingAs($user)
            ->get(route('attempts.show', $attempt))
            ->assertOk()
            ->assertSee('Good, but say how you would verify it.');
    }
}
