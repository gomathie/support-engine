<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

/**
 * Sections B and C of the PILOT Technical Support Employee Examination, from
 * docs/PILOT_Technical_Support_Employee_Exam_EN2.md.
 *
 *   Section B — 15 written answers, five points each
 *   Section C — 18 practical tasks, demonstrated to an examiner
 *
 * Section A is not seeded here: PilotExamSeeder owns it, including its answer
 * key and per-question explanations. A course can only have one final exam —
 * a quiz attached to neither a module nor a lesson — so these two are scoped to
 * the course's last module instead. Seeding them as final exams too would give
 * the course two, and Course::finalQuiz() would pick between them arbitrarily,
 * silently deciding whether the course completes and a certificate issues.
 *
 * Both sections are written answers, so neither can be machine-marked. They are
 * graded by an admin or a manager through the grading queue in the admin panel.
 */
class WrittenExamSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::query()->where('category', 'TRACK 1')->first();

        if (! $course) {
            $this->command?->warn('No TRACK 1 course found — skipping the written exam.');

            return;
        }

        // Queried directly rather than through $course->modules(), whose
        // relation already applies orderBy('position') — adding orderByDesc to
        // it appends a second clause the first one wins, which quietly returned
        // the *first* module.
        $lastModule = CourseModule::query()
            ->where('course_id', $course->id)
            ->orderByDesc('position')
            ->first();

        if (! $lastModule) {
            $this->command?->warn('TRACK 1 has no modules — skipping the written exam.');

            return;
        }

        $exam = require database_path('seeders/data/final_exam.php');

        $this->seedSectionB($course, $lastModule, $exam);
        $this->seedSectionC($course, $lastModule, $exam);
    }

    /** @param array<string, mixed> $exam */
    private function seedSectionB(Course $course, CourseModule $module, array $exam): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'PILOT Technical Support Examination — Section B: Written Questions',
            ],
            [
                'course_module_id' => $module->id,
                'lesson_id' => null,
                'description' => 'Fifteen written questions, five points each. Recommended time '
                    .'90–120 minutes. Answers are read and marked by an examiner, so your result '
                    .'is not immediate.',
                'passing_score' => 70,
                'max_attempts' => 2,
                'time_limit_minutes' => 120,
                'shuffle_questions' => false,
                'shuffle_options' => false,
                'show_feedback' => true,
                'is_published' => false,
            ],
        );

        // Rebuilt rather than merged: prompt text is the identity here, and a
        // re-seed should not leave a half-updated paper behind.
        $quiz->questions()->forceDelete();

        foreach ($exam['section_b'] as $index => $item) {
            QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'type' => QuestionType::Written,
                'prompt' => 'B'.$item['number'].'. '.$item['title']."\n\n".$item['prompt'],
                'points' => 5,
                'position' => $index + 1,
                'marking_guidance' => $this->rubric(),
            ]);
        }

        $this->command?->info(sprintf(
            'Section B: %d written questions (%d points) on "%s".',
            count($exam['section_b']),
            count($exam['section_b']) * 5,
            $module->title,
        ));
    }

    /** @param array<string, mixed> $exam */
    private function seedSectionC(Course $course, CourseModule $module, array $exam): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'PILOT Technical Support Examination — Section C: Practical',
            ],
            [
                'course_module_id' => $module->id,
                'lesson_id' => null,
                'description' => 'Performed in the test environment. For each task, demonstrate '
                    .'the completed configuration to the examiner and explain the sequence of '
                    .'actions. The examiner records the outcome against each part.',
                'passing_score' => 70,
                'max_attempts' => null,
                'time_limit_minutes' => null,
                'shuffle_questions' => false,
                'shuffle_options' => false,
                'show_feedback' => true,
                'is_published' => false,
            ],
        );

        $quiz->questions()->forceDelete();

        foreach ($exam['section_c'] as $index => $item) {
            QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'type' => QuestionType::Written,
                'prompt' => 'Part '.$item['number'].'. '.$item['title']."\n\n".$item['prompt'],
                'points' => 5,
                'position' => $index + 1,
                'marking_guidance' => 'Mark on the demonstrated configuration and the explanation '
                    .'of the sequence, not on the written description alone. The examinee should '
                    .'be able to show the result in the test environment.',
            ]);
        }

        $this->command?->info(sprintf(
            'Section C: %d practical parts (%d points) on "%s".',
            count($exam['section_c']),
            count($exam['section_c']) * 5,
            $module->title,
        ));
    }

    /** The scoring guidance the source document sets out for Section B. */
    private function rubric(): string
    {
        return implode("\n", [
            'Five points. A complete answer contains at least three parts:',
            '  1. What must be checked or configured.',
            '  2. In what sequence the actions are performed.',
            '  3. How successful completion is confirmed.',
            '',
            'For situational questions, also look for: possible causes, the data to request from '
                .'the client, how to reproduce the issue, and the criteria for escalating to 2nd '
                .'line or development.',
            '',
            'Listing interface items without explaining the sequence or the verification is not a '
                .'complete answer.',
        ]);
    }
}
