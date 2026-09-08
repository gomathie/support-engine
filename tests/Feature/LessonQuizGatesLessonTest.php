<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteLesson;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\CompletionRequirement;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * A quiz at the end of every lesson.
 *
 * This is the finer-grained gate: a lesson whose completion_requirement is
 * `quiz` is not finished by reading it, only by passing its quiz. Because a
 * course is not complete until every lesson is, this gates the course too —
 * by a different route from the module-level knowledge checks.
 */
class TopicQuizGatesTopicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** @return array{0: Course, 1: Lesson, 2: Quiz, 3: QuizQuestion} */
    private function topicWithQuiz(): array
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create(['title' => 'Module 1']);

        $lesson = Lesson::factory()->for($module, 'module')->create([
            'title' => 'Define: Object, Sensor, Contract, Account',
            'completion_requirement' => CompletionRequirement::Quiz,
        ]);

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'module_id' => null,
            'title' => 'Quiz: Object, Sensor, Contract, Account',
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $question = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create(['points' => 1]);

        return [$course->fresh(), $lesson->fresh(), $quiz, $question];
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

    /** Opening it is not passing it. */
    public function test_viewing_a_quiz_topic_does_not_complete_it(): void
    {
        [$course, $lesson] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        app(CompleteLesson::class)->touch($user, $lesson);

        $this->assertFalse(
            $lesson->completedBy($user),
            'A lesson gated on a quiz must not complete just because it was opened.',
        );
    }

    /** Nor can it be completed by posting at the completion endpoint. */
    public function test_it_cannot_be_completed_without_passing_the_quiz(): void
    {
        [$course, $lesson] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        try {
            app(CompleteLesson::class)->handle($user, $lesson);

            $this->fail('Completing a quiz-gated lesson without passing should be refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('passing its quiz', $e->errors()['lesson'][0]);
        }

        $this->assertFalse($lesson->completedBy($user));
    }

    public function test_failing_the_quiz_leaves_the_topic_open(): void
    {
        [$course, $lesson, $quiz, $question] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);
        $this->sit($user, $quiz, $question, correctly: false);

        $this->assertFalse($lesson->completedBy($user));
    }

    /** Passing it completes the lesson, with no further action from the trainee. */
    public function test_passing_the_quiz_completes_the_topic(): void
    {
        [$course, $lesson, $quiz, $question] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);
        $this->sit($user, $quiz, $question, correctly: true);

        $this->assertTrue(
            $lesson->completedBy($user),
            'Passing the lesson quiz should complete the lesson without another click.',
        );
    }

    // ─── AND THEREFORE THE COURSE ────────────────────────────

    /**
     * The indirect gate: a course is not complete until every lesson is, so an
     * unpassed lesson quiz keeps the whole course open. This is the route the
     * per-lesson quizzes take, distinct from the module-level knowledge checks.
     */
    public function test_an_unpassed_topic_quiz_keeps_the_course_incomplete(): void
    {
        [$course, $lesson, $quiz, $question] = $this->topicWithQuiz();

        // A second lesson that only needs reading, so the course hinges on the
        // quiz-gated one alone.
        Lesson::factory()->for($lesson->module, 'module')->create([
            'completion_requirement' => CompletionRequirement::View,
        ]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course->fresh());

        foreach ($course->fresh()->lessons as $t) {
            if ($t->completion_requirement === CompletionRequirement::View) {
                app(CompleteLesson::class)->handle($user, $t);
            }
        }

        $status = fn () => $user->courseProgress()->where('course_id', $course->id)->first()?->status;

        $this->assertSame(ProgressStatus::InProgress, $status());

        $this->sit($user, $quiz, $question, correctly: true);

        $this->assertSame(
            ProgressStatus::Completed,
            $status(),
            'Passing the last outstanding lesson quiz should complete the course.',
        );
    }

    // ─── WHAT THE SEEDED CONTENT ACTUALLY DOES ───────────────

    /**
     * The authored curriculum, not a fixture: every lesson carrying a quiz must
     * be gated on it, or the quiz is decoration.
     */
    public function test_every_seeded_topic_with_a_quiz_is_gated_on_it(): void
    {
        $this->seed(\Database\Seeders\TrainingContentSeeder::class);
        $this->seed(\Database\Seeders\LessonContentSeeder::class);

        $gatedTopicIds = Quiz::query()
            ->whereNotNull('lesson_id')
            ->pluck('lesson_id');

        $this->assertGreaterThan(20, $gatedTopicIds->count(), 'The curriculum should carry lesson quizzes.');

        $notGated = Lesson::query()
            ->whereIn('id', $gatedTopicIds)
            ->where('completion_requirement', '!=', CompletionRequirement::Quiz->value)
            ->pluck('title');

        $this->assertTrue(
            $notGated->isEmpty(),
            'These lessons have a quiz but do not require it: '.$notGated->implode(' · '),
        );
    }
}
