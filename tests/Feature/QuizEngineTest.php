<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuizEngineTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(array $quizAttributes = []): array
    {
        $course = Course::factory()->create();

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            ...$quizAttributes,
        ]);

        $user = $this->employee();
        app(EnrollEmployee::class)->handle($user, $course);

        return [$course, $quiz, $user];
    }

    public function test_a_correct_submission_scores_full_marks_and_passes(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $question = QuizQuestion::factory()->for($quiz)->withOptions(4, [0])->create();
        $correct = $question->options()->where('is_correct', true)->first();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$correct->id], 'text' => null],
        ]);

        $attempt->refresh();

        $this->assertSame('100.00', $attempt->score);
        $this->assertTrue($attempt->passed);
        $this->assertSame(1, $attempt->points_earned);
    }

    public function test_a_wrong_submission_scores_zero_and_fails(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $question = QuizQuestion::factory()->for($quiz)->withOptions(4, [0])->create();
        $wrong = $question->options()->where('is_correct', false)->first();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$wrong->id], 'text' => null],
        ]);

        $attempt->refresh();

        $this->assertSame('0.00', $attempt->score);
        $this->assertFalse($attempt->passed);
    }

    /**
     * All-or-nothing. Partial credit would let somebody tick every box and
     * score on every multiple-choice question.
     */
    public function test_multiple_choice_needs_every_correct_option_and_no_others(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $question = QuizQuestion::factory()
            ->for($quiz)
            ->multipleChoice()
            ->withOptions(4, [0, 1])
            ->create();

        $options = $question->options()->orderBy('position')->get();

        // Only one of the two correct options.
        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$options[0]->id], 'text' => null],
        ]);
        $this->assertSame('0.00', $attempt->fresh()->score);

        // Both correct, plus an incorrect one.
        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            [
                'question_id' => $question->id,
                'option_ids' => [$options[0]->id, $options[1]->id, $options[2]->id],
                'text' => null,
            ],
        ]);
        $this->assertSame('0.00', $attempt->fresh()->score);

        // Exactly the two correct options.
        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            [
                'question_id' => $question->id,
                'option_ids' => [$options[0]->id, $options[1]->id],
                'text' => null,
            ],
        ]);
        $this->assertSame('100.00', $attempt->fresh()->score);
    }

    public function test_short_answer_matching_ignores_case_and_padding(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $question = QuizQuestion::factory()->for($quiz)->shortAnswer()->create();
        $question->options()->create(['label' => 'Layer 7', 'is_correct' => true, 'position' => 1]);

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [], 'text' => '  LAYER   7 '],
        ]);

        $this->assertTrue($attempt->fresh()->passed);
    }

    public function test_unanswered_questions_are_recorded_and_score_zero(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        $answered = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();
        $skipped = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            [
                'question_id' => $answered->id,
                'option_ids' => [$answered->options()->where('is_correct', true)->first()->id],
                'text' => null,
            ],
        ]);

        $attempt->refresh();

        $this->assertSame(2, $attempt->answers()->count());
        $this->assertSame('50.00', $attempt->score);
        $this->assertFalse($attempt->passed);
    }

    public function test_the_attempt_limit_is_enforced(): void
    {
        [$course, $quiz, $user] = $this->scenario(['max_attempts' => 2]);

        QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();

        foreach (range(1, 2) as $i) {
            $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
            app(GradeQuizAttempt::class)->handle($attempt, []);
        }

        $this->expectException(ValidationException::class);

        app(StartQuizAttempt::class)->handle($user, $quiz);
    }

    /**
     * A refreshed tab must resume the same attempt rather than burning one of
     * a limited number.
     */
    public function test_an_unfinished_attempt_is_resumed_not_duplicated(): void
    {
        [$course, $quiz, $user] = $this->scenario(['max_attempts' => 1]);

        QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();

        $first = app(StartQuizAttempt::class)->handle($user, $quiz);
        $second = app(StartQuizAttempt::class)->handle($user, $quiz);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $quiz->attempts()->count());
    }

    /**
     * Raising the pass mark must not retroactively fail somebody who already
     * sat the quiz under the old rules.
     */
    public function test_the_pass_mark_is_snapshotted_onto_the_attempt(): void
    {
        [$course, $quiz, $user] = $this->scenario(['passing_score' => 50]);

        $question = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();
        $correct = $question->options()->where('is_correct', true)->first();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        $quiz->update(['passing_score' => 100]);

        app(GradeQuizAttempt::class)->handle($attempt->fresh(), [
            ['question_id' => $question->id, 'option_ids' => [$correct->id], 'text' => null],
        ]);

        $this->assertSame(50, $attempt->fresh()->passing_score);
        $this->assertTrue($attempt->fresh()->passed);
    }

    // ─── SECURITY ────────────────────────────────────────────

    /**
     * The single most important assertion in this suite: the answer key must
     * not reach the browser before the attempt is graded.
     */
    public function test_the_answer_key_is_absent_from_the_taking_a_quiz_payload(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        QuizQuestion::factory()->for($quiz)->withOptions(4, [2])->create();

        $response = $this->actingAs($user)
            ->post(route('quizzes.start', [$course->slug, $quiz->id]));

        $response->assertOk();

        $payload = $response->getContent();

        $this->assertStringNotContainsString('is_correct', $payload);
        $this->assertStringNotContainsString('explanation', $payload);
    }

    public function test_an_employee_cannot_read_another_employees_attempt(): void
    {
        [$course, $quiz, $owner] = $this->scenario();

        QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();

        $attempt = app(StartQuizAttempt::class)->handle($owner, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, []);

        $intruder = $this->employee();

        $this->actingAs($intruder)
            ->get(route('attempts.show', $attempt))
            ->assertForbidden();
    }

    public function test_an_employee_cannot_submit_another_employees_attempt(): void
    {
        [$course, $quiz, $owner] = $this->scenario();

        $question = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();
        $attempt = app(StartQuizAttempt::class)->handle($owner, $quiz);

        $intruder = $this->employee();

        $this->actingAs($intruder)
            ->post(route('quizzes.submit', [$course->slug, $quiz->id]), [
                'attempt_id' => $attempt->id,
                'answers' => [],
            ])
            ->assertForbidden();
    }

    /**
     * Options from another quiz must be rejected, or a crafted submission could
     * distort the point total.
     */
    public function test_options_from_another_quiz_are_rejected(): void
    {
        [$course, $quiz, $user] = $this->scenario();

        QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();

        $otherQuiz = Quiz::factory()->create(['course_id' => $course->id]);
        $otherQuestion = QuizQuestion::factory()->for($otherQuiz)->withOptions(2, [0])->create();
        $foreignOption = $otherQuestion->options()->first();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        $this->actingAs($user)
            ->post(route('quizzes.submit', [$course->slug, $quiz->id]), [
                'attempt_id' => $attempt->id,
                'answers' => [
                    [
                        'question_id' => $otherQuestion->id,
                        'option_ids' => [$foreignOption->id],
                    ],
                ],
            ])
            ->assertSessionHasErrors();
    }
}
