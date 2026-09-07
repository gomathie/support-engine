<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Lesson;
use App\Models\Quiz;
use Database\Seeders\LessonContentSeeder;
use Database\Seeders\TrainingContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seeded lesson content belongs to the trainers, not to the seeder.
 *
 * Two things have to hold or the "content is editable" promise is hollow: a
 * trainer must be able to change it, and nothing must quietly change it back.
 */
class LessonContentEditableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TrainingContentSeeder::class);
        $this->seed(LessonContentSeeder::class);
    }

    private function writtenLesson(): Lesson
    {
        return Lesson::query()
            ->where('title', 'Define: Object, Sensor, Contract, Account')
            ->firstOrFail();
    }

    public function test_the_seeder_writes_real_content(): void
    {
        $this->assertGreaterThan(1000, strlen($this->writtenLesson()->content));
    }

    public function test_a_trainer_can_open_and_edit_a_seeded_lesson(): void
    {
        $lesson = $this->writtenLesson();
        $trainer = $this->trainer();

        $this->assertTrue($trainer->can('update', $lesson));

        $this->actingAs($trainer)
            ->get(LessonResource::getUrl('edit', ['record' => $lesson]))
            ->assertSuccessful();
    }

    public function test_a_trainer_can_open_the_knowledge_check(): void
    {
        $quiz = Quiz::query()->where('title', 'Lesson 1 — knowledge check')->firstOrFail();

        $this->actingAs($this->trainer())
            ->get(QuizResource::getUrl('edit', ['record' => $quiz]))
            ->assertSuccessful();
    }

    /**
     * The one that matters. A trainer edits a lesson; somebody re-runs the
     * seeders during a deployment; the trainer's work must still be there.
     */
    public function test_re_running_the_seeder_does_not_overwrite_an_edit(): void
    {
        $lesson = $this->writtenLesson();

        $lesson->forceFill(['content' => '<p>Rewritten by Marcus after the calibration meeting.</p>'])->save();

        $this->seed(LessonContentSeeder::class);

        $this->assertSame(
            '<p>Rewritten by Marcus after the calibration meeting.</p>',
            $lesson->fresh()->content,
            'A trainer\'s edit must survive a re-seed.',
        );
    }

    public function test_re_running_the_seeder_does_not_rewrite_an_edited_question(): void
    {
        $quiz = Quiz::query()->where('title', 'Lesson 1 — knowledge check')->firstOrFail();

        $question = $quiz->questions()->first();
        $question->forceFill(['prompt' => 'A question Marcus rewrote.'])->save();

        $this->seed(LessonContentSeeder::class);

        $this->assertSame('A question Marcus rewrote.', $question->fresh()->prompt);
    }

    /**
     * Filament's rich editor is TipTap-based and drops nodes it has no
     * extension for. Its toolbar covers headings, lists, blockquotes, tables
     * and inline marks — but not `div` or definition lists. Content using those
     * would be silently stripped the first time a trainer pressed save.
     */
    public function test_seeded_content_uses_only_markup_the_editor_round_trips(): void
    {
        $bodies = Lesson::query()
            ->whereNotNull('content')
            ->pluck('content')
            ->implode("\n");

        foreach (['<div', '<dl>', '<dt>', '<dd>', '<figure', '<span'] as $unsupported) {
            $this->assertStringNotContainsString(
                $unsupported,
                $bodies,
                "The rich editor cannot round-trip {$unsupported} — a trainer's first save would strip it.",
            );
        }

        // What it does support, and what the lessons are built from.
        $this->assertStringContainsString('<blockquote>', $bodies);
        $this->assertStringContainsString('<ul>', $bodies);
        $this->assertStringContainsString('<h2', $bodies);
    }

    /** A topic with no source in the docs is left visibly open, not invented. */
    public function test_an_unsourced_topic_is_marked_as_needing_input(): void
    {
        $lesson = Lesson::query()
            ->where('title', 'Explain what a Mapping Contract is and its use case')
            ->firstOrFail();

        $this->assertStringContainsString('not written yet', $lesson->content);
        $this->assertStringContainsString('does not appear', $lesson->content);
    }

    public function test_the_knowledge_check_is_published_and_scoped_to_the_lesson(): void
    {
        $quiz = Quiz::query()->where('title', 'Lesson 1 — knowledge check')->firstOrFail();

        $this->assertTrue($quiz->is_published);
        $this->assertSame(70, $quiz->passing_score);
        $this->assertNotNull($quiz->course_module_id, 'It is a lesson check, not the course final exam.');
        $this->assertCount(4, $quiz->questions);

        foreach ($quiz->questions as $question) {
            $this->assertCount(4, $question->options, 'Four options, one best answer.');
            $this->assertCount(1, $question->options->where('is_correct', true));
            $this->assertNotEmpty($question->explanation, 'Every question explains its key.');
        }
    }
}
