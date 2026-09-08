<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use Database\Seeders\LessonContentSeeder;
use Database\Seeders\PilotExamSeeder;
use Database\Seeders\TrainingContentSeeder;
use Database\Seeders\WrittenExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Level 1 requires Sections A, B and C, and all three are final exams.
 *
 * Decided 2026-09-08, closing Appendix B item 2. They are classified as final
 * exams — attached to neither a module nor a lesson — which is what they are.
 * That only became safe once completion required *every* published final exam
 * rather than `finalQuiz()->first()`.
 *
 * The cost is deliberate and worth keeping visible: B and C are 33 written
 * answers between them, none of which a machine can mark. A trainee cannot
 * finish the course until a trainer has read all of them.
 */
class ExaminationGatesLevelOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TrainingContentSeeder::class);
        $this->seed(LessonContentSeeder::class);
        $this->seed(PilotExamSeeder::class);
        $this->seed(WrittenExamSeeder::class);
    }

    private function course(): Course
    {
        return Course::query()->where('slug', '1st-line-support')->firstOrFail();
    }

    public function test_all_three_sections_are_classified_as_final_exams(): void
    {
        $sections = Quiz::query()
            ->where('title', 'like', '%Examination%Section%')
            ->get();

        $this->assertCount(3, $sections);

        foreach ($sections as $section) {
            $this->assertNull(
                $section->module_id,
                "\"{$section->title}\" is a final exam, so it belongs to no module.",
            );

            $this->assertNull($section->lesson_id);

            $this->assertSame(
                Quiz::SCOPE_FINAL,
                $section->scope(),
                "\"{$section->title}\" should report itself as a final exam.",
            );

            $this->assertTrue(
                (bool) $section->is_published,
                "\"{$section->title}\" must be published to gate anything.",
            );
        }
    }

    /** All of them gate, which is the whole point of the reclassification. */
    public function test_every_section_is_among_the_gating_finals(): void
    {
        $gating = $this->course()
            ->finalQuiz()
            ->where('is_published', true)
            ->pluck('title');

        foreach (['Section A', 'Section B', 'Section C'] as $section) {
            $this->assertTrue(
                $gating->contains(fn (string $title) => str_contains($title, $section)),
                "{$section} is not among the final exams that gate completion.",
            );
        }
    }

    /**
     * The operational cost, asserted so nobody is surprised by it: 33 answers
     * that only a person can mark stand between a trainee and a finished
     * course. This is the §6(a) bottleneck — watch KPI 7.
     */
    public function test_the_examination_needs_a_trainer_to_mark_it(): void
    {
        $manual = Quiz::query()
            ->where('title', 'like', '%Examination%Section%')
            ->where('is_published', true)
            ->get()
            ->flatMap->questions
            ->filter(fn ($question) => $question->requiresManualGrading());

        $this->assertSame(
            33,
            $manual->count(),
            'Sections B and C are 15 + 18 hand-marked answers. If this number moves, '
            .'the trainer workload assumption behind the decision has moved with it.',
        );

        // Every one of them carries guidance, or two trainers will mark the
        // same answer differently.
        foreach ($manual as $question) {
            $this->assertNotEmpty(
                $question->marking_guidance,
                'A hand-marked question without marking guidance invites rubric drift.',
            );
        }
    }

    public function test_section_a_remains_machine_marked(): void
    {
        $sectionA = Quiz::query()->where('title', 'like', '%Section A%')->firstOrFail();

        $manual = $sectionA->questions->filter(fn ($q) => $q->requiresManualGrading());

        $this->assertCount(
            0,
            $manual,
            'Section A is 40 multiple-choice questions and should mark itself.',
        );
    }

    /** The examination module holds the briefing; the papers are course-level. */
    public function test_the_examination_lesson_carries_no_quizzes_of_its_own(): void
    {
        $module = \App\Models\Module::query()
            ->where('subtitle', 'PILOT Technical Support Employee Examination')
            ->first();

        if (! $module) {
            $this->markTestSkipped('The examination module is not seeded here.');
        }

        $this->assertSame(
            0,
            Quiz::query()->where('module_id', $module->getKey())->count(),
            'The sections were reclassified as final exams and should have left the module.',
        );
    }
}
