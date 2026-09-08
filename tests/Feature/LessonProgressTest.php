<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteTopic;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    private function courseWithLessons(int $count = 4): Course
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        Topic::factory()->count($count)->for($lesson, 'lesson')->create();

        return $course->fresh();
    }

    public function test_completing_a_lesson_persists_and_recalculates(): void
    {
        $course = $this->courseWithLessons(4);
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        $topic = $course->topics()->first();

        $this->actingAs($user)
            ->post(route('topics.complete', [$course->slug, $topic->slug]))
            ->assertRedirect();

        $this->assertDatabaseHas('topic_progress', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        $progress = $user->courseProgress()->where('course_id', $course->id)->first();

        $this->assertSame(1, $progress->completed_topics);
        $this->assertSame(4, $progress->total_topics);
        $this->assertSame('25.00', $progress->percentage);
        $this->assertSame(ProgressStatus::InProgress, $progress->status);
    }

    public function test_completing_every_lesson_completes_the_course(): void
    {
        $course = $this->courseWithLessons(3);
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->topics as $topic) {
            app(CompleteTopic::class)->handle($user, $topic);
        }

        $progress = $user->courseProgress()->where('course_id', $course->id)->first();

        $this->assertSame(ProgressStatus::Completed, $progress->status);
        $this->assertSame('100.00', $progress->percentage);
        $this->assertNotNull($progress->completed_at);
    }

    public function test_unticking_a_lesson_reverses_completion(): void
    {
        $course = $this->courseWithLessons(2);
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->topics as $topic) {
            app(CompleteTopic::class)->handle($user, $topic);
        }

        $this->assertTrue(
            $user->courseProgress()->where('course_id', $course->id)->first()->isComplete()
        );

        $first = $course->topics()->first();

        $this->actingAs($user)
            ->delete(route('topics.uncomplete', [$course->slug, $first->slug]))
            ->assertRedirect();

        $progress = $user->courseProgress()->where('course_id', $course->id)->first();

        $this->assertSame(ProgressStatus::InProgress, $progress->status);
        $this->assertNull($progress->completed_at);
    }

    /**
     * Unpublished topics must not be counted, or publishing one later would
     * silently drop somebody from 100% back to incomplete after they had
     * already been issued a certificate.
     */
    public function test_unpublished_lessons_are_excluded_from_the_total(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        Topic::factory()->count(2)->for($lesson, 'lesson')->create();
        Topic::factory()->for($lesson, 'lesson')->unpublished()->create();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->topics()->where('is_published', true)->get() as $topic) {
            app(CompleteTopic::class)->handle($user, $topic);
        }

        $progress = $user->courseProgress()->where('course_id', $course->id)->first();

        $this->assertSame(2, $progress->total_topics);
        $this->assertSame(ProgressStatus::Completed, $progress->status);
    }

    /**
     * A topic gated on a quiz must not be tickable by hand — otherwise the
     * completion requirement is advisory and the assessment can be skipped by
     * posting to the endpoint.
     */
    public function test_a_quiz_gated_lesson_cannot_be_ticked_off_directly(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();
        $topic = Topic::factory()->for($lesson, 'lesson')->requiresQuiz()->create();

        Quiz::factory()->create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
        ]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->expectException(ValidationException::class);

        app(CompleteTopic::class)->handle($user, $topic->fresh());
    }

    public function test_an_employee_cannot_complete_a_lesson_they_are_not_enrolled_in(): void
    {
        $course = $this->courseWithLessons(2);
        $user = $this->trainee();

        $topic = $course->topics()->first();

        $this->actingAs($user)
            ->post(route('topics.complete', [$course->slug, $topic->slug]))
            ->assertForbidden();

        $this->assertDatabaseMissing('topic_progress', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);
    }

    public function test_progress_can_be_reset(): void
    {
        $course = $this->courseWithLessons(2);
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $course);
        app(CompleteTopic::class)->handle($user, $course->topics()->first());

        $this->actingAs($user)
            ->delete(route('progress.reset', $course->slug))
            ->assertRedirect();

        $progress = $user->courseProgress()->where('course_id', $course->id)->first();

        $this->assertSame(0, $progress->completed_topics);
        $this->assertSame(ProgressStatus::NotStarted, $progress->status);
    }
}
