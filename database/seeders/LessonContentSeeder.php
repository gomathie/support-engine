<?php

namespace Database\Seeders;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Enums\QuestionType;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\PracticalTask;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Puts real content into the curriculum, one lesson at a time.
 *
 * The seeded courses were titles with empty bodies — §1 of the implementation
 * plan calls that out as the largest gap in the product. This seeder fills them
 * from files in `database/seeders/content/`, each one written against
 * docs.pilot-gps.com.
 *
 * ---------------------------------------------------------------------------
 * ADDING THE NEXT LESSON
 *
 *   1. Copy `content/track1_lesson_01.php` to the next number.
 *   2. Set `module_subtitle` to the subtitle of the module it belongs to —
 *      matched on subtitle rather than title, because "Lesson 1" exists in more
 *      than one course.
 *   3. Key each entry by the lesson title already in the curriculum.
 *   4. Name the documentation page every lesson is drawn from. If the lesson is
 *      not in the docs, do not write it: set `needs_input` and say what is
 *      missing. An invented PILOT fact is worse than a visible gap, because a
 *      trainee will carry it onto a call.
 *   5. Add the file to `files()` below.
 *
 * Idempotent: re-running updates bodies in place rather than duplicating them,
 * so content can be revised here and re-applied.
 * ---------------------------------------------------------------------------
 */
class LessonContentSeeder extends Seeder
{
    /** @return array<int, string> */
    private function files(): array
    {
        return [
            'track1_lesson_01.php',
            'track1_lesson_02.php',
            'track1_lesson_03.php',
            'track1_lesson_04.php',
            'track1_lesson_05.php',
            'track1_lesson_06.php',
            'track1_lesson_07.php',
            'track1_lesson_08.php',
            'track1_lesson_09.php',
            'track1_lesson_10.php',
            'track1_lesson_11.php',
            'track1_lesson_12.php',

            // Admin panel (TRACK 2)
            'track2_lesson_01.php',
            'track2_lesson_02.php',
            'track2_lesson_03.php',
            'track2_lesson_04.php',
        ];
    }

    public function run(): void
    {
        foreach ($this->files() as $file) {
            $path = database_path('seeders/content/'.$file);

            if (! file_exists($path)) {
                $this->command?->warn("Missing content file: {$file}");

                continue;
            }

            $this->apply(require $path);
        }
    }

    /** @param  array<string, mixed>  $content */
    private function apply(array $content): void
    {
        /*
         * Matched case-insensitively on purpose.
         *
         * A content file naming "Objects, Partners, and Finances" against a
         * module recorded as "Objects, partners, and finances" is the same
         * module, and three lessons' worth of writing sat unapplied behind
         * exactly that. The seeder prints a warning and carries on, so the only
         * symptom is a course that stays empty.
         */
        $module = Module::query()
            ->whereRaw('lower(subtitle) = ?', [mb_strtolower(trim($content['module_subtitle']))])
            ->first();

        if (! $module) {
            $this->command?->warn('No module with subtitle: '.$content['module_subtitle']);

            return;
        }

        $written = 0;
        $open = 0;
        $kept = 0;

        foreach ($content['lessons'] as $title => $lesson) {
            $existing = $module->lessons()->where('title', $title)->first();

            if (! $existing) {
                $this->command?->warn("No lesson titled \"{$title}\" in {$module->title}.");

                continue;
            }

            /*
             * Never overwrite a lesson somebody has edited.
             *
             * Trainers author in the admin panel — that is the point of the
             * content being editable. A seeder that reapplies its own version
             * on every run would silently destroy their work, and they would
             * have no way of knowing it had happened.
             *
             * Set LESSON_CONTENT_OVERWRITE=1 to reapply deliberately, which is
             * for revising the source files during development.
             */
            if (filled($existing->content) && ! $this->overwriting()) {
                $kept++;

                continue;
            }

            $hasQuiz = isset($lesson['quiz']) && ! empty($lesson['quiz']);

            $existing->forceFill([
                'type' => LessonType::RichText,
                'content' => trim($lesson['body']),
                'estimated_minutes' => $lesson['estimated_minutes'] ?? $existing->estimated_minutes,

                /*
                 * Modules carrying a quiz require passing it to finish.
                 * Other lessons record reading on open.
                 */
                'completion_requirement' => $hasQuiz ? CompletionRequirement::Quiz : CompletionRequirement::View,
            ])->save();

            if ($hasQuiz) {
                $this->seedLessonQuiz($existing, $lesson['quiz']);
            }

            isset($lesson['needs_input']) ? $open++ : $written++;
        }

        $this->seedQuiz($module, $content['quiz'] ?? null);
        $this->seedPracticalTask($module, $content['practical_task'] ?? $content['practical_tasks'] ?? null);

        $this->command?->info(
            $module->title.': '.$written.' written'
            .($open > 0 ? ', '.$open.' awaiting subject-matter input' : '')
            .($kept > 0 ? ', '.$kept.' left alone (already has content)' : '')
        );

        if ($kept > 0 && ! $this->overwriting()) {
            $this->command?->comment(
                '  Existing content was not replaced. Use LESSON_CONTENT_OVERWRITE=1 to reapply.'
            );
        }
    }

    /** Reapplying the source files is deliberate, never the default. */
    private function overwriting(): bool
    {
        return filter_var(env('LESSON_CONTENT_OVERWRITE', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * The knowledge check at the end of the lesson.
     *
     * Module-scoped — `module_id` set, `lesson_id` null — so it is a
     * lesson test rather than the course's final exam. A course may only have
     * one of those, and it is not this.
     *
     * @param  array<string, mixed>|null  $quiz
     */
    private function seedQuiz(Module $module, ?array $quiz): void
    {
        if (! $quiz) {
            return;
        }

        /*
         * Same rule as the lesson bodies: once a knowledge check exists, it
         * belongs to whoever has been maintaining it. Rewriting the questions
         * would discard a trainer's corrections — and the options are deleted
         * and recreated below, so it would not even be a merge.
         */
        $already = Quiz::query()
            ->where('module_id', $module->getKey())
            ->whereNull('lesson_id')
            ->exists();

        if ($already && ! $this->overwriting()) {
            $this->command?->comment('  Knowledge check already exists — left alone.');

            return;
        }

        /*
         * Keyed on the module, not the title.
         *
         * A module has exactly one knowledge check — that is what this method
         * is for. Keying on the title meant a retitle created a second one
         * beside the first, and every published module-scoped quiz gates the
         * course, so the duplicate did not sit quietly. It happened twice: the
         * lesson-to-module rename produced thirteen of them, and then "Final
         * Assessment" against "Final assessment" produced another. Keying on
         * the module makes a retitle a rename.
         */
        $record = Quiz::query()->updateOrCreate(
            [
                'course_id' => $module->course_id,
                'module_id' => $module->getKey(),
                'lesson_id' => null,
            ],
            [
                'title' => $quiz['title'],
                'description' => $quiz['description'] ?? null,
                'passing_score' => $quiz['passing_score'] ?? 70,
                'max_attempts' => $quiz['max_attempts'] ?? 3,
                'time_limit_minutes' => $quiz['time_limit_minutes'] ?? null,
                'is_published' => true,
            ],
        );

        $this->seedQuestions($record, $quiz['questions']);
    }

    /**
     * Dedicated knowledge check attached to a specific lesson.
     *
     * @param  array<string, mixed>  $quiz
     */
    private function seedLessonQuiz(Lesson $lesson, array $quiz): void
    {
        $title = $quiz['title'] ?? ($lesson->title.' — Knowledge check');

        $already = Quiz::query()
            ->where('lesson_id', $lesson->getKey())
            ->exists();

        if ($already && ! $this->overwriting()) {
            return;
        }

        // Keyed on the lesson rather than the title, for the reason given on
        // seedQuiz(): a lesson has one check, and a retitle must be a rename.
        $record = Quiz::query()->updateOrCreate(
            [
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->getKey(),
            ],
            [
                'title' => $title,
                'module_id' => $lesson->module_id,
                'description' => $quiz['description'] ?? null,
                'passing_score' => $quiz['passing_score'] ?? 70,
                'max_attempts' => $quiz['max_attempts'] ?? 3,
                'time_limit_minutes' => $quiz['time_limit_minutes'] ?? null,
                'is_published' => true,
            ],
        );

        $this->seedQuestions($record, $quiz['questions']);
    }

    /**
     * Write the paper, and take away what is no longer on it.
     *
     * Questions are matched by prompt, so rewording one adds a question rather
     * than changing it. Without the prune, revising a quiz leaves the old
     * questions in place beside the new ones — which is how a five-question
     * check came to have nine, four of them asserting things the documentation
     * does not say.
     *
     * A question somebody has answered is never removed.
     * `quiz_answers.quiz_question_id` is `restrictOnDelete` deliberately:
     * deleting a question must not rewrite the history of attempts graded
     * against it. Those are left in place, and the count says so.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function seedQuestions(Quiz $quiz, array $questions): void
    {
        foreach ($questions as $position => $question) {
            $this->seedQuestion($quiz, $question, $position + 1);
        }

        $keep = collect($questions)->pluck('prompt')->all();

        $superseded = QuizQuestion::query()
            ->where('quiz_id', $quiz->getKey())
            ->whereNotIn('prompt', $keep)
            ->get();

        $stranded = 0;

        foreach ($superseded as $question) {
            $answered = QuizAnswer::query()
                ->where('quiz_question_id', $question->getKey())
                ->exists();

            if ($answered) {
                $stranded++;

                continue;
            }

            $question->options()->delete();
            $question->forceDelete();
        }

        if ($stranded > 0) {
            $this->command?->warn(
                "  {$quiz->title}: {$stranded} superseded question(s) kept — they have been answered."
            );
        }
    }

    /** @param  array<string, mixed>  $question */
    private function seedQuestion(Quiz $quiz, array $question, int $position): void
    {
        $record = QuizQuestion::query()->updateOrCreate(
            [
                'quiz_id' => $quiz->getKey(),
                'prompt' => $question['prompt'],
            ],
            [
                'type' => QuestionType::from($question['type']),
                'points' => $question['points'] ?? 1,
                'explanation' => $question['explanation'] ?? null,
                'position' => $position,
            ],
        );

        // Rewritten wholesale: options carry no history worth preserving, and
        // matching them on text would strand a corrected typo as a duplicate.
        $record->options()->delete();

        foreach ($question['options'] as $index => $option) {
            QuizOption::query()->create([
                'quiz_question_id' => $record->getKey(),
                'label' => $option['text'],
                'is_correct' => $option['correct'],
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * Practical task(s) attached to the module or its lessons.
     *
     * @param  array<string, mixed>|array<int, array<string, mixed>>|null  $taskData
     */
    private function seedPracticalTask(Module $module, ?array $taskData): void
    {
        if (! $taskData) {
            return;
        }

        $tasks = isset($taskData['title']) ? [$taskData] : $taskData;

        foreach ($tasks as $position => $data) {
            if (! isset($data['title'])) {
                continue;
            }

            /*
             * Matched on the slug, not the title.
             *
             * The slug is derived from the title on create and then never
             * changes, and `practical_tasks.(course_id, slug)` is unique. Match
             * on the title and a retitle is not an update: either it collides
             * with the existing row's slug and the seeder dies, or it slips
             * past and leaves two tasks where there was one. Both have happened
             * — the same shape of bug as the course-slug duplicates in
             * `2026_09_08_000420`.
             *
             * Matching on the slug means a reworded title updates the row it
             * belongs to. Changing a task's *meaning* still needs a new title
             * whose slug differs, which is the honest way to get a new task.
             */
            $slug = Str::slug(Str::limit($data['title'], 60, ''));

            $already = PracticalTask::query()
                ->where('course_id', $module->course_id)
                ->where('slug', $slug)
                ->exists();

            if ($already && ! $this->overwriting()) {
                $this->command?->comment("  Practical task \"{$data['title']}\" already exists — left alone.");

                continue;
            }

            $lessonId = null;
            if (isset($data['lesson_title'])) {
                $lessonId = $module->lessons()->where('title', $data['lesson_title'])->value('id');
            }

            PracticalTask::query()->updateOrCreate(
                [
                    'course_id' => $module->course_id,
                    'slug' => $slug,
                ],
                [
                    'title' => $data['title'],
                    'lesson_id' => $lessonId,
                    'brief' => $data['brief'] ?? '',
                    'submission_instructions' => $data['submission_instructions'] ?? null,
                    'expected_evidence' => $data['expected_evidence'] ?? null,
                    'required_evidence' => $data['required_evidence'] ?? [],
                    'requires_screenshot' => $data['requires_screenshot'] ?? true,
                    'estimated_minutes' => $data['estimated_minutes'] ?? 15,
                    'position' => $position + 1,
                    'is_published' => true,
                    'requires_second_marker' => $data['requires_second_marker'] ?? false,
                ]
            );
        }
    }
}
