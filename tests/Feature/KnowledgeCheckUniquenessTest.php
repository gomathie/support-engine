<?php

namespace Tests\Feature;

use App\Models\Quiz;
use Database\Seeders\LessonContentSeeder;
use Database\Seeders\TrainingContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A module has exactly one knowledge check, and re-seeding does not change that.
 *
 * This has broken twice, both times silently and both times expensively.
 * `LessonContentSeeder` matched a module's knowledge check **by title**, so a
 * retitle created a second one rather than renaming the first:
 *
 *   · The lesson-to-module rename left **thirteen** duplicates. 1st-line
 *     support went from twelve knowledge checks to twenty-four — the same
 *     questions twice each, and a trainee who passed one still blocked by its
 *     twin.
 *   · "Module 4 — Final Assessment" against "Module 4 — Final assessment"
 *     produced another, on a difference of one letter's case.
 *
 * Nothing complains when it happens. `RecalculateCourseProgress` and
 * `QuizPolicy` both require every published module-scoped quiz to be passed, so
 * a duplicate just quietly doubles a module's requirement.
 *
 * The seeder now keys on the module. These assertions run it twice and check
 * that the second run changed nothing, which is the property that actually
 * matters.
 */
class KnowledgeCheckUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private function seedCurriculum(): void
    {
        $this->seed(TrainingContentSeeder::class);
        $this->seed(LessonContentSeeder::class);
    }

    /** @return array<int, string> module id => titles, for modules with more than one check */
    private function duplicated(): array
    {
        return Quiz::query()
            ->whereNull('lesson_id')
            ->whereNotNull('module_id')
            ->get()
            ->groupBy('module_id')
            ->filter(fn ($checks) => $checks->count() > 1)
            ->map(fn ($checks) => $checks->pluck('title')->implode(' · '))
            ->all();
    }

    public function test_the_seeded_curriculum_has_one_knowledge_check_per_module(): void
    {
        $this->seedCurriculum();

        $checks = Quiz::query()->whereNull('lesson_id')->whereNotNull('module_id')->count();

        $this->assertGreaterThan(
            10,
            $checks,
            'The curriculum should carry module knowledge checks at all.',
        );

        $duplicated = $this->duplicated();

        $this->assertSame(
            [],
            $duplicated,
            'These modules carry more than one knowledge check: '
            .collect($duplicated)->implode(' | '),
        );
    }

    /**
     * Re-running the seeder is routine — it is how content revisions are
     * applied. It must not multiply the papers.
     */
    public function test_re_seeding_does_not_duplicate_knowledge_checks(): void
    {
        $this->seedCurriculum();

        $before = Quiz::query()->whereNull('lesson_id')->whereNotNull('module_id')->count();

        $this->seed(LessonContentSeeder::class);

        $this->assertSame(
            $before,
            Quiz::query()->whereNull('lesson_id')->whereNotNull('module_id')->count(),
            'A second seeder run added knowledge checks.',
        );

        $this->assertSame([], $this->duplicated());
    }

    /** The same trap, one level down: a lesson has one quiz, not one per title. */
    public function test_re_seeding_does_not_duplicate_lesson_quizzes(): void
    {
        $this->seedCurriculum();

        $before = Quiz::query()->whereNotNull('lesson_id')->count();

        $this->seed(LessonContentSeeder::class);

        $duplicated = Quiz::query()
            ->whereNotNull('lesson_id')
            ->get()
            ->groupBy('lesson_id')
            ->filter(fn ($quizzes) => $quizzes->count() > 1)
            ->map(fn ($quizzes) => $quizzes->pluck('title')->implode(' · '))
            ->all();

        $this->assertSame($before, Quiz::query()->whereNotNull('lesson_id')->count());
        $this->assertSame(
            [],
            $duplicated,
            'These lessons carry more than one quiz: '.collect($duplicated)->implode(' | '),
        );
    }

    /**
     * Revising a quiz replaces its questions rather than adding to them.
     *
     * Questions are matched by prompt, so a reworded question is a new one. Two
     * Admin panel checks reached nine questions this way — five current and
     * four superseded, the old ones asserting things the documentation does not
     * say, and all nine on the paper.
     */
    public function test_re_seeding_does_not_multiply_questions(): void
    {
        $this->seedCurriculum();

        $before = Quiz::query()
            ->whereNotNull('module_id')
            ->withCount('questions')
            ->pluck('questions_count', 'id')
            ->all();

        $this->seed(LessonContentSeeder::class);

        $after = Quiz::query()
            ->whereNotNull('module_id')
            ->withCount('questions')
            ->pluck('questions_count', 'id')
            ->all();

        $this->assertSame($before, $after, 'A second seeder run changed the question counts.');
    }
}
