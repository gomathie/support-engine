<?php

namespace Tests\Feature;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What an assessment is attached to is expressed in the admin as one choice,
 * but stored as a pair of nullable foreign keys. These cover the translation
 * between the two — the place a stale id could quietly turn a final exam back
 * into a module test.
 */
class QuizScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_scope_clears_both_keys(): void
    {
        $data = QuizResource::applyScope(
            ['course_id' => 1, 'lesson_id' => 7, 'topic_id' => 9],
            Quiz::SCOPE_FINAL,
        );

        $this->assertNull($data['lesson_id']);
        $this->assertNull($data['topic_id']);
    }

    public function test_lesson_scope_keeps_the_lesson_and_clears_the_topic(): void
    {
        $data = QuizResource::applyScope(
            ['course_id' => 1, 'lesson_id' => 7, 'topic_id' => 9],
            Quiz::SCOPE_LESSON,
        );

        $this->assertSame(7, $data['lesson_id']);
        $this->assertNull($data['topic_id']);
    }

    public function test_topic_scope_keeps_the_topic_and_clears_the_lesson(): void
    {
        $data = QuizResource::applyScope(
            ['course_id' => 1, 'lesson_id' => 7, 'topic_id' => 9],
            Quiz::SCOPE_TOPIC,
        );

        $this->assertNull($data['lesson_id']);
        $this->assertSame(9, $data['topic_id']);
    }

    public function test_scope_is_derived_from_the_stored_keys(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();
        $topic = Topic::factory()->for($lesson, 'lesson')->create();

        $final = Quiz::factory()->create(['course_id' => $course->id]);

        $lessonQuiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
        ]);

        $topicQuiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
        ]);

        $this->assertSame(Quiz::SCOPE_FINAL, $final->scope());
        $this->assertSame(Quiz::SCOPE_LESSON, $lessonQuiz->scope());
        $this->assertSame(Quiz::SCOPE_TOPIC, $topicQuiz->scope());

        $this->assertSame('Final exam', $final->scopeLabel());
        $this->assertSame('Knowledge check', $lessonQuiz->scopeLabel());
        $this->assertSame('Topic check', $topicQuiz->scopeLabel());
    }

    /**
     * Only the course-level quiz gates completion. A module test or a topic
     * check must not hold the whole course open.
     */
    public function test_only_the_final_exam_counts_as_the_course_assessment(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        Quiz::factory()->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
        ]);

        $this->assertSame(0, $course->finalQuiz()->count());

        $final = Quiz::factory()->create(['course_id' => $course->id]);

        $this->assertSame(1, $course->fresh()->finalQuiz()->count());
        $this->assertTrue($course->fresh()->finalQuiz()->first()->is($final));
    }
}
