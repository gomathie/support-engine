<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sections B and C of the PILOT Technical Support Employee Examination, from
 * docs/PILOT_Technical_Support_Employee_Exam_EN2.md.
 *
 *   Section B — 15 written answers, five points each
 *   Section C — 18 practical tasks, demonstrated to an examiner
 *
 * Section A is not seeded here: PilotExamSeeder owns it, including its answer
 * key and per-question explanations.
 *
 * All three sections are **final exams** — attached to neither a module nor a
 * lesson — because that is what they are. A course may have several, and
 * `RecalculateCourseProgress` requires every published one to be passed. That
 * matters: while it read `finalQuiz()->first()`, a second course-scoped exam was
 * sittable and counted for nothing.
 *
 * Both sections are written answers, so neither can be machine-marked. They are
 * graded by a trainer or an admin through the grading queue in the admin panel.
 *
 * **Both are live** (decided 2026-09-08): Level 1 requires Sections A, B and C,
 * closing Appendix B item 2 of the implementation plan. So a trainee cannot
 * finish the course until a trainer has marked all 33 written answers. That is
 * the intended rigour, and it is also the bottleneck §6(a) of the plan warns
 * about: watch KPI 7 (trainer workload) once the cohort grows.
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

        /*
         * The examination module, by name.
         *
         * This used to take whichever module sorted last, which was only
         * accidentally the right one — adding a module after it would have
         * silently moved Sections B and C somewhere else.
         *
         * Queried directly rather than through $course->modules(), whose
         * relation already applies orderBy('position') — adding orderByDesc to
         * it appends a second clause the first one wins, which quietly returned
         * the *first* module.
         */
        $lastModule = Module::query()
            ->where('course_id', $course->id)
            ->where('subtitle', 'PILOT Technical Support Employee Examination')
            ->first()
            ?? Module::query()
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
    private function seedSectionB(Course $course, Module $module, array $exam): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'PILOT Technical Support Examination — Section B: Written Questions',
            ],
            [
                'module_id' => null,
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
                'is_published' => true,
            ],
        );

        // Rebuilt rather than merged: prompt text is the identity here, and a
        // re-seed should not leave a half-updated paper behind.
        $this->clearUnansweredQuestions($quiz);

        foreach ($exam['section_b'] as $index => $item) {
            // Matched on prompt, not blindly created: a question somebody has
            // already answered survives clearUnansweredQuestions(), and
            // creating it again would leave the paper with two of each.
            QuizQuestion::query()->updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'prompt' => 'B'.$item['number'].'. '.$item['title']."\n\n".$item['prompt'],
                ],
                [
                    'type' => QuestionType::Written,
                    'points' => 5,
                    'position' => $index + 1,
                    'marking_guidance' => $this->rubric(),
                ],
            );
        }

        $this->command?->info(sprintf(
            'Section B: %d written questions (%d points) on "%s".',
            count($exam['section_b']),
            count($exam['section_b']) * 5,
            $module->title,
        ));
    }

    /** @param array<string, mixed> $exam */
    private function seedSectionC(Course $course, Module $module, array $exam): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'PILOT Technical Support Examination — Section C: Practical',
            ],
            [
                'module_id' => null,
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
                'is_published' => true,
            ],
        );

        $this->clearUnansweredQuestions($quiz);

        foreach ($exam['section_c'] as $index => $item) {
            QuizQuestion::query()->updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'prompt' => 'Part '.$item['number'].'. '.$item['title']."\n\n".$item['prompt'],
                ],
                [
                    'type' => QuestionType::Written,
                    'points' => 5,
                    'position' => $index + 1,
                    'marking_guidance' => 'Mark on the demonstrated configuration and the explanation '
                        .'of the sequence, not on the written description alone. The examinee should '
                        .'be able to show the result in the test environment.',
                ],
            );
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

    /**
     * Clear the paper without destroying anybody's marks.
     *
     * This used to be `$quiz->questions()->forceDelete()`, which worked exactly
     * until somebody sat the exam: `quiz_answers.quiz_question_id` is
     * `restrictOnDelete` on purpose, so that deleting a question cannot quietly
     * rewrite the history of attempts already graded against it. Re-seeding
     * then failed with a foreign key violation.
     *
     * Questions somebody has answered are left alone — they are re-matched by
     * prompt below and updated in place. Only the untouched ones are removed,
     * which is what keeps a re-seed from leaving a half-updated paper behind.
     */
    private function clearUnansweredQuestions(Quiz $quiz): void
    {
        $answered = DB::table('quiz_answers')
            ->join('quiz_questions', 'quiz_questions.id', '=', 'quiz_answers.quiz_question_id')
            ->where('quiz_questions.quiz_id', $quiz->getKey())
            ->pluck('quiz_answers.quiz_question_id');

        $quiz->questions()
            ->whereNotIn('id', $answered)
            ->forceDelete();
    }
}
