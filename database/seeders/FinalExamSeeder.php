<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

/**
 * The PILOT Technical Support Employee Examination, from
 * docs/PILOT_Technical_Support_Employee_Exam_EN2.md.
 *
 *   Section A — 40 multiple choice, one point each, auto-marked
 *   Section B — 15 written answers, five points each, marked by an examiner
 *   Section C — 18 practical tasks, demonstrated and marked by an examiner
 *
 * Section A is seeded WITHOUT an answer key: the source document's
 * "Answer Key for the Examiner" heading is present but empty, and guessing the
 * key would fail people who answered correctly. Every Section A question shows
 * as "needs answer key" in the admin until somebody ticks the right option.
 *
 * A and B together are the final exam. C is a practical assessment attached to
 * the last module, because demonstrating a configuration to an examiner is not
 * the same activity as sitting a paper.
 */
class FinalExamSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::query()->where('category', 'TRACK 1')->first();

        if (! $course) {
            $this->command?->warn('No TRACK 1 course found — skipping the final exam.');

            return;
        }

        $exam = require database_path('seeders/data/final_exam.php');

        $this->seedWrittenExam($course, $exam);
        $this->seedPracticalExam($course, $exam);
    }

    /** @param array<string, mixed> $exam */
    private function seedWrittenExam(Course $course, array $exam): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'course_module_id' => null,
                'lesson_id' => null,
            ],
            [
                'title' => 'PILOT Technical Support Employee Examination',
                'description' => 'Section A — 40 multiple-choice questions (60 minutes). '
                    .'Section B — 15 written answers, five points each (90–120 minutes). '
                    .'Written answers are marked by an examiner, so your result is not '
                    .'immediate.',
                'passing_score' => 70,
                'max_attempts' => 2,

                // 60 minutes for A plus 120 for B. One sitting, because the
                // attempt clock is per attempt rather than per section.
                'time_limit_minutes' => 180,

                // Order carries meaning here — Section A then Section B — so
                // nothing is shuffled.
                'shuffle_questions' => false,
                'shuffle_options' => false,

                'show_feedback' => true,

                // Draft until Section A has an answer key. Publishing it now
                // would mean every multiple-choice question marks wrong.
                'is_published' => false,
            ],
        );

        // Rebuilt rather than merged: prompt text is the identity here, and a
        // re-seed should not leave a half-updated paper behind.
        $quiz->questions()->forceDelete();

        $position = 0;

        foreach ($exam['section_a'] as $item) {
            $question = QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'type' => QuestionType::SingleChoice,
                'prompt' => 'A'.$item['number'].'. '.$item['prompt'],
                'points' => 1,
                'position' => ++$position,
                'marking_guidance' => 'Answer key not supplied in the source document — '
                    .'tick the correct option before publishing.',
            ]);

            foreach ($item['options'] as $index => $label) {
                $question->options()->create([
                    'label' => $label,

                    // Deliberately unset. See the class docblock.
                    'is_correct' => false,

                    'position' => $index + 1,
                ]);
            }
        }

        foreach ($exam['section_b'] as $item) {
            QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'type' => QuestionType::Written,
                'prompt' => 'B'.$item['number'].'. '.$item['title']."\n\n".$item['prompt'],
                'points' => 5,
                'position' => ++$position,
                'marking_guidance' => $this->writtenRubric(),
            ]);
        }

        $this->command?->info(sprintf(
            'Final exam: %d multiple choice (no answer key yet) + %d written = %d points.',
            count($exam['section_a']),
            count($exam['section_b']),
            count($exam['section_a']) + (count($exam['section_b']) * 5),
        ));
    }

    /** @param array<string, mixed> $exam */
    private function seedPracticalExam(Course $course, array $exam): void
    {
        $lastModule = $course->modules()->orderByDesc('position')->first();

        if (! $lastModule) {
            return;
        }

        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'course_module_id' => $lastModule->id,
                'lesson_id' => null,
            ],
            [
                'title' => 'PILOT Technical Support — Practical Examination',
                'description' => 'Section C. Performed in the test environment. For each task, '
                    .'demonstrate the completed configuration to the examiner and explain the '
                    .'sequence of actions. The examiner records the outcome against each part.',
                'passing_score' => 70,
                'max_attempts' => null,
                'time_limit_minutes' => null,
                'shuffle_questions' => false,
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
                    .'of the sequence, not on the written description alone.',
            ]);
        }

        $this->command?->info(sprintf(
            'Practical exam: %d parts on "%s".',
            count($exam['section_c']),
            $lastModule->title,
        ));
    }

    /** The scoring guidance the source document sets out for Section B. */
    private function writtenRubric(): string
    {
        return implode("\n", [
            'Five points. A complete answer contains at least three parts:',
            '  1. What must be checked or configured.',
            '  2. In what sequence the actions are performed.',
            '  3. How successful completion is confirmed.',
            '',
            'For situational questions, also look for: possible causes, the data to request '
                .'from the client, how to reproduce the issue, and the criteria for escalating '
                .'to 2nd line or development.',
            '',
            'Listing interface items without explaining the sequence or the verification is '
                .'not a complete answer.',
        ]);
    }
}
