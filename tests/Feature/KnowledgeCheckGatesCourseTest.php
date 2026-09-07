<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteTopic;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Every topic ends with a knowledge check, and passing it is required.
 *
 * Before this, a module-scoped quiz was decoration: a trainee could skip every
 * one and still finish the course on the final exam alone, which made "you must
 * pass" untrue of the thing sitting at the end of each topic.
 */
class KnowledgeCheckGatesCourseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** A course whose single topic ends with a knowledge check. */
    private function courseWithCheck(): array
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create(['title' => 'Lesson 1']);

        Topic::factory()->count(2)->for($lesson, 'lesson')->create();

        $check = Quiz::factory()->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'topic_id' => null,
            'title' => 'Lesson 1 — knowledge check',
            'passing_score' => 70,
            'max_attempts' => 2,
            'is_published' => true,
        ]);

        $question = QuizQuestion::factory()->for($check)->withOptions(2, [0])->create(['points' => 1]);

        return [$course->fresh(), $lesson, $check, $question];
    }

    private function readEverything(User $user, Course $course): void
    {
        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->topics as $topic) {
            app(CompleteTopic::class)->handle($user, $topic);
        }
    }

    private function sit(User $user, Quiz $check, QuizQuestion $question, bool $correctly): void
    {
        $option = $question->options()
            ->where('is_correct', $correctly)
            ->first();

        $attempt = app(StartQuizAttempt::class)->handle($user, $check);

        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$option->id], 'text' => null],
        ]);
    }

    private function statusFor(User $user, Course $course): ?ProgressStatus
    {
        return $user->courseProgress()->where('course_id', $course->id)->first()?->status;
    }

    // ─── THE GATE ────────────────────────────────────────────

    public function test_reading_the_lesson_without_the_check_does_not_finish_the_course(): void
    {
        [$course] = $this->courseWithCheck();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        $this->assertSame(
            ProgressStatus::InProgress,
            $this->statusFor($user, $course),
            'An unpassed knowledge check must keep the course open.',
        );
    }

    public function test_passing_the_check_finishes_the_course(): void
    {
        [$course, , $check, $question] = $this->courseWithCheck();
        $user = $this->trainee();

        $this->readEverything($user, $course);
        $this->sit($user, $check, $question, correctly: true);

        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user->fresh(), $course));
    }

    public function test_failing_the_check_leaves_the_course_open_while_attempts_remain(): void
    {
        [$course, , $check, $question] = $this->courseWithCheck();
        $user = $this->trainee();

        $this->readEverything($user, $course);
        $this->sit($user, $check, $question, correctly: false);

        $this->assertSame(ProgressStatus::InProgress, $this->statusFor($user->fresh(), $course));
    }

    /**
     * Out of attempts on a check they cannot pass is terminal. Leaving it at
     * "in progress" would have them waiting for something that cannot happen.
     */
    public function test_running_out_of_attempts_on_a_check_fails_the_course(): void
    {
        [$course, , $check, $question] = $this->courseWithCheck();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        // max_attempts is 2.
        $this->sit($user, $check, $question, correctly: false);
        $this->sit($user, $check, $question, correctly: false);

        $this->assertSame(ProgressStatus::Failed, $this->statusFor($user->fresh(), $course));
    }

    /** An unpublished check is not a gate — nobody can sit it. */
    public function test_an_unpublished_check_does_not_block_completion(): void
    {
        [$course, , $check] = $this->courseWithCheck();
        $check->update(['is_published' => false]);

        $user = $this->trainee();
        $this->readEverything($user, $course);

        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user, $course));
    }

    /** Every topic's check counts, not just the first. */
    public function test_all_checks_across_the_course_must_be_passed(): void
    {
        [$course, , $first, $firstQuestion] = $this->courseWithCheck();

        $second = Lesson::factory()->for($course)->create(['title' => 'Lesson 2']);
        Topic::factory()->for($second, 'lesson')->create();

        $secondCheck = Quiz::factory()->create([
            'course_id' => $course->id,
            'lesson_id' => $second->id,
            'topic_id' => null,
            'title' => 'Lesson 2 — knowledge check',
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $secondQuestion = QuizQuestion::factory()->for($secondCheck)->withOptions(2, [0])->create(['points' => 1]);

        $user = $this->trainee();
        $this->readEverything($user, $course->fresh());

        $this->sit($user, $first, $firstQuestion, correctly: true);
        $this->assertSame(ProgressStatus::InProgress, $this->statusFor($user->fresh(), $course));

        $this->sit($user, $secondCheck, $secondQuestion, correctly: true);
        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user->fresh(), $course));
    }

    // ─── VISIBILITY ──────────────────────────────────────────

    public function test_the_course_page_shows_the_check_at_the_end_of_the_lesson(): void
    {
        [$course, , $check] = $this->courseWithCheck();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->get(route('courses.show', $course->slug))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('lessons.0.knowledge_check.title', 'Lesson 1 — knowledge check')
                ->where('lessons.0.knowledge_check.passing_score', 70)
                ->where('lessons.0.knowledge_check.passed', false));
    }

    /** A topic with no check is a gap an author should be able to see. */
    public function test_a_module_reports_whether_it_has_a_check(): void
    {
        [, $lesson] = $this->courseWithCheck();

        $this->assertTrue($lesson->hasKnowledgeCheck());

        $bare = Lesson::factory()->for(Course::factory()->create())->create();

        $this->assertFalse($bare->hasKnowledgeCheck());
        $this->assertNull($bare->knowledgeCheck());
    }
}
