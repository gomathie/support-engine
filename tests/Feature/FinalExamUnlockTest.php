<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteLesson;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * The final exam opens last.
 *
 * Every lesson must be read and every knowledge check passed before a trainee can
 * sit a final paper. An examination is a summative test of the whole course —
 * sitting it with half the material unread wastes an attempt and tells nobody
 * anything.
 *
 * Enforced in `QuizPolicy::attempt()`, not by hiding the button. The button was
 * already hidden; the policy was not checking, so posting straight at the
 * endpoint started an attempt regardless.
 */
class FinalExamUnlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /**
     * A course with one readable lesson, one knowledge check and one final exam.
     *
     * @return array{0: Course, 1: Lesson, 2: array{0: Quiz, 1: QuizQuestion}, 2: array}
     */
    private function scenario(): array
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create(['title' => 'Module 1']);

        $lesson = Lesson::factory()->for($module, 'module')->create();

        $check = Quiz::factory()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_id' => null,
            'title' => 'Module 1 — knowledge check',
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $final = Quiz::factory()->create([
            'course_id' => $course->id,
            'module_id' => null,
            'lesson_id' => null,
            'title' => 'Final exam',
            'passing_score' => 70,
            'is_published' => true,
        ]);

        return [
            $course->fresh(),
            $lesson,
            [$check, QuizQuestion::factory()->for($check)->withOptions(2, [0])->create(['points' => 1])],
            [$final, QuizQuestion::factory()->for($final)->withOptions(2, [0])->create(['points' => 1])],
        ];
    }

    private function sit(User $user, Quiz $quiz, QuizQuestion $question, bool $correctly): void
    {
        $option = $question->options()->where('is_correct', $correctly)->first();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$option->id], 'text' => null],
        ]);
    }

    // ─── THE GATE ────────────────────────────────────────────

    public function test_the_final_is_locked_while_topics_are_unread(): void
    {
        [$course, , , [$final]] = $this->scenario();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->assertFalse(
            $user->can('attempt', $final),
            'The examination must not open before the material has been read.',
        );
    }

    /** The one this rule exists for: reading everything is not enough. */
    public function test_the_final_stays_locked_until_the_knowledge_check_is_passed(): void
    {
        [$course, $lesson, [$check, $checkQuestion], [$final]] = $this->scenario();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);
        app(CompleteLesson::class)->handle($user, $lesson);

        $this->assertFalse(
            $user->fresh()->can('attempt', $final),
            'Every lesson is read, but the knowledge check is outstanding.',
        );

        $this->sit($user, $check, $checkQuestion, correctly: true);

        $this->assertTrue(
            $user->fresh()->can('attempt', $final),
            'With the material read and every check passed, the examination opens.',
        );
    }

    public function test_failing_a_knowledge_check_does_not_open_the_final(): void
    {
        [$course, $lesson, [$check, $checkQuestion], [$final]] = $this->scenario();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);
        app(CompleteLesson::class)->handle($user, $lesson);

        $this->sit($user, $check, $checkQuestion, correctly: false);

        $this->assertFalse($user->fresh()->can('attempt', $final));
    }

    /** Hiding the button was never the control. */
    public function test_posting_straight_at_the_endpoint_is_refused(): void
    {
        [$course, , , [$final]] = $this->scenario();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->post(route('quizzes.start', [$course->slug, $final->id]))
            ->assertForbidden();
    }

    // ─── WHAT IT MUST NOT BREAK ──────────────────────────────

    /** A knowledge check is not a final exam and opens immediately. */
    public function test_a_knowledge_check_is_not_gated_on_itself(): void
    {
        [$course, , [$check]] = $this->scenario();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->assertTrue(
            $user->can('attempt', $check),
            'A module check must be sittable as soon as the trainee reaches it.',
        );
    }

    /** An unpublished check cannot be passed, so it must not lock the door. */
    public function test_an_unpublished_check_does_not_hold_the_final_shut(): void
    {
        [$course, $lesson, [$check], [$final]] = $this->scenario();

        $check->update(['is_published' => false]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);
        app(CompleteLesson::class)->handle($user, $lesson);

        $this->assertTrue($user->fresh()->can('attempt', $final));
    }

    /** A course with nothing but a final exam still lets somebody sit it. */
    public function test_a_course_with_no_other_assessment_opens_normally(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module, 'module')->create();

        $final = Quiz::factory()->create([
            'course_id' => $course->id,
            'module_id' => null,
            'lesson_id' => null,
            'is_published' => true,
        ]);

        QuizQuestion::factory()->for($final)->withOptions(2, [0])->create(['points' => 1]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course->fresh());
        app(CompleteLesson::class)->handle($user, $lesson);

        $this->assertTrue($user->fresh()->can('attempt', $final));
    }

    /** Sitting one paper does not depend on having passed another. */
    public function test_final_papers_do_not_gate_each_other(): void
    {
        [$course, $lesson, [$check, $checkQuestion], [$firstFinal]] = $this->scenario();

        $secondFinal = Quiz::factory()->create([
            'course_id' => $course->id,
            'module_id' => null,
            'lesson_id' => null,
            'title' => 'Second paper',
            'is_published' => true,
        ]);

        QuizQuestion::factory()->for($secondFinal)->withOptions(2, [0])->create(['points' => 1]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);
        app(CompleteLesson::class)->handle($user, $lesson);
        $this->sit($user, $check, $checkQuestion, correctly: true);

        $user = $user->fresh();

        $this->assertTrue($user->can('attempt', $firstFinal));
        $this->assertTrue(
            $user->can('attempt', $secondFinal),
            'The papers of one examination are peers, not a sequence.',
        );
    }
}
