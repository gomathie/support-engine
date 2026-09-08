<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteTopic;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\CompletionRequirement;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * A quiz at the end of every topic.
 *
 * This is the finer-grained gate: a topic whose completion_requirement is
 * `quiz` is not finished by reading it, only by passing its quiz. Because a
 * course is not complete until every topic is, this gates the course too —
 * by a different route from the lesson-level knowledge checks.
 */
class TopicQuizGatesTopicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** @return array{0: Course, 1: Topic, 2: Quiz, 3: QuizQuestion} */
    private function topicWithQuiz(): array
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create(['title' => 'Lesson 1']);

        $topic = Topic::factory()->for($lesson, 'lesson')->create([
            'title' => 'Define: Object, Sensor, Contract, Account',
            'completion_requirement' => CompletionRequirement::Quiz,
        ]);

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'lesson_id' => null,
            'title' => 'Quiz: Object, Sensor, Contract, Account',
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $question = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create(['points' => 1]);

        return [$course->fresh(), $topic->fresh(), $quiz, $question];
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
        [$course, $topic] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        app(CompleteTopic::class)->touch($user, $topic);

        $this->assertFalse(
            $topic->completedBy($user),
            'A topic gated on a quiz must not complete just because it was opened.',
        );
    }

    /** Nor can it be completed by posting at the completion endpoint. */
    public function test_it_cannot_be_completed_without_passing_the_quiz(): void
    {
        [$course, $topic] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        try {
            app(CompleteTopic::class)->handle($user, $topic);

            $this->fail('Completing a quiz-gated topic without passing should be refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('passing its quiz', $e->errors()['topic'][0]);
        }

        $this->assertFalse($topic->completedBy($user));
    }

    public function test_failing_the_quiz_leaves_the_topic_open(): void
    {
        [$course, $topic, $quiz, $question] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);
        $this->sit($user, $quiz, $question, correctly: false);

        $this->assertFalse($topic->completedBy($user));
    }

    /** Passing it completes the topic, with no further action from the trainee. */
    public function test_passing_the_quiz_completes_the_topic(): void
    {
        [$course, $topic, $quiz, $question] = $this->topicWithQuiz();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);
        $this->sit($user, $quiz, $question, correctly: true);

        $this->assertTrue(
            $topic->completedBy($user),
            'Passing the topic quiz should complete the topic without another click.',
        );
    }

    // ─── AND THEREFORE THE COURSE ────────────────────────────

    /**
     * The indirect gate: a course is not complete until every topic is, so an
     * unpassed topic quiz keeps the whole course open. This is the route the
     * per-topic quizzes take, distinct from the lesson-level knowledge checks.
     */
    public function test_an_unpassed_topic_quiz_keeps_the_course_incomplete(): void
    {
        [$course, $topic, $quiz, $question] = $this->topicWithQuiz();

        // A second topic that only needs reading, so the course hinges on the
        // quiz-gated one alone.
        Topic::factory()->for($topic->lesson, 'lesson')->create([
            'completion_requirement' => CompletionRequirement::View,
        ]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course->fresh());

        foreach ($course->fresh()->topics as $t) {
            if ($t->completion_requirement === CompletionRequirement::View) {
                app(CompleteTopic::class)->handle($user, $t);
            }
        }

        $status = fn () => $user->courseProgress()->where('course_id', $course->id)->first()?->status;

        $this->assertSame(ProgressStatus::InProgress, $status());

        $this->sit($user, $quiz, $question, correctly: true);

        $this->assertSame(
            ProgressStatus::Completed,
            $status(),
            'Passing the last outstanding topic quiz should complete the course.',
        );
    }

    // ─── WHAT THE SEEDED CONTENT ACTUALLY DOES ───────────────

    /**
     * The authored curriculum, not a fixture: every topic carrying a quiz must
     * be gated on it, or the quiz is decoration.
     */
    public function test_every_seeded_topic_with_a_quiz_is_gated_on_it(): void
    {
        $this->seed(\Database\Seeders\TrainingContentSeeder::class);
        $this->seed(\Database\Seeders\LessonContentSeeder::class);

        $gatedTopicIds = Quiz::query()
            ->whereNotNull('topic_id')
            ->pluck('topic_id');

        $this->assertGreaterThan(20, $gatedTopicIds->count(), 'The curriculum should carry topic quizzes.');

        $notGated = Topic::query()
            ->whereIn('id', $gatedTopicIds)
            ->where('completion_requirement', '!=', CompletionRequirement::Quiz->value)
            ->pluck('title');

        $this->assertTrue(
            $notGated->isEmpty(),
            'These topics have a quiz but do not require it: '.$notGated->implode(' · '),
        );
    }
}
