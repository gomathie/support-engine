<?php

namespace Database\Seeders;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Enums\QuestionType;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Quiz;
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
 *   4. Name the documentation page every lesson is drawn from. If the topic is
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
        $module = CourseModule::query()
            ->where('subtitle', $content['module_subtitle'])
            ->first();

        if (! $module) {
            $this->command?->warn('No module with subtitle: '.$content['module_subtitle']);

            return;
        }

        $written = 0;
        $open = 0;

        foreach ($content['lessons'] as $title => $lesson) {
            $existing = $module->lessons()->where('title', $title)->first();

            if (! $existing) {
                $this->command?->warn("No lesson titled \"{$title}\" in {$module->title}.");

                continue;
            }

            $existing->forceFill([
                'type' => LessonType::RichText,
                'content' => trim($lesson['body']),
                'estimated_minutes' => $lesson['estimated_minutes'] ?? $existing->estimated_minutes,

                /*
                 * Reading is recorded, not self-attested. The lesson makes no
                 * claim about competence — the knowledge check below does.
                 */
                'completion_requirement' => CompletionRequirement::View,
            ])->save();

            isset($lesson['needs_input']) ? $open++ : $written++;
        }

        $this->seedQuiz($module, $content['quiz'] ?? null);

        $this->command?->info(
            $module->title.': '.$written.' written'
            .($open > 0 ? ', '.$open.' awaiting subject-matter input' : '')
        );
    }

    /**
     * The knowledge check at the end of the lesson.
     *
     * Module-scoped — `course_module_id` set, `lesson_id` null — so it is a
     * lesson test rather than the course's final exam. A course may only have
     * one of those, and it is not this.
     *
     * @param  array<string, mixed>|null  $quiz
     */
    private function seedQuiz(CourseModule $module, ?array $quiz): void
    {
        if (! $quiz) {
            return;
        }

        $record = Quiz::query()->updateOrCreate(
            [
                'course_id' => $module->course_id,
                'course_module_id' => $module->getKey(),
                'title' => $quiz['title'],
            ],
            [
                'description' => $quiz['description'] ?? null,
                'passing_score' => $quiz['passing_score'] ?? 70,
                'max_attempts' => $quiz['max_attempts'] ?? 3,
                'time_limit_minutes' => $quiz['time_limit_minutes'] ?? null,
                'is_published' => true,
            ],
        );

        foreach ($quiz['questions'] as $position => $question) {
            $this->seedQuestion($record, $question, $position + 1);
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
}
