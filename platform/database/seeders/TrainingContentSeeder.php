<?php

namespace Database\Seeders;

use App\Enums\CompletionRequirement;
use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Enums\QuestionType;
use App\Enums\Role;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TrainingContentSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::query()->role(Role::Admin->value)->first();

        $content = require database_path('seeders/data/training_content.php');

        foreach ($content as $position => $courseData) {
            $course = Course::query()->updateOrCreate(
                ['slug' => Str::slug($courseData['title'])],
                [
                    'title' => $courseData['title'],
                    'category' => $courseData['category'],
                    'summary' => $courseData['summary'],
                    'description' => $courseData['description'],
                    'difficulty' => $courseData['difficulty'],
                    'is_required' => $courseData['is_required'],
                    'estimated_minutes' => $courseData['estimated_minutes'],
                    'due_days' => $courseData['due_days'] ?? null,
                    'status' => CourseStatus::Published,
                    'instructor_id' => $instructor?->id,
                    'created_by' => $instructor?->id,
                ],
            );

            foreach ($courseData['modules'] as $modulePosition => $moduleData) {
                $module = CourseModule::query()->updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => $moduleData['title'],
                    ],
                    [
                        'subtitle' => $moduleData['subtitle'],
                        'description' => $moduleData['description'],
                        'position' => $modulePosition + 1,
                        'is_published' => true,
                    ],
                );

                foreach ($moduleData['lessons'] as $lessonPosition => $title) {
                    Lesson::query()->updateOrCreate(
                        [
                            'course_module_id' => $module->id,
                            'slug' => Str::slug(Str::limit($title, 60, '')),
                        ],
                        [
                            'course_id' => $course->id,
                            'title' => $title,
                            'type' => LessonType::RichText,

                            // The prototype's items were checkboxes, so they map
                            // onto the requirement that reproduces that: the
                            // employee ticks them off themselves.
                            'completion_requirement' => CompletionRequirement::Acknowledge,

                            'position' => $lessonPosition + 1,
                            'is_published' => true,
                        ],
                    );
                }
            }
        }

        $this->seedFinalAssessment();
    }

    /**
     * The prototype had "Complete the final test" as a checklist item with
     * nothing behind it. This is that test, made real, on the 1st-line course.
     */
    private function seedFinalAssessment(): void
    {
        $course = Course::query()->where('category', 'TRACK 1')->first();

        if (! $course) {
            return;
        }

        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'course_module_id' => null,
                'lesson_id' => null,
            ],
            [
                'title' => 'PILOT 1st-line final assessment',
                'description' => 'Covers the full two weeks. You need 70% to pass, and you have three attempts.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'time_limit_minutes' => 30,
                'shuffle_questions' => true,
                'shuffle_options' => true,
                'show_feedback' => true,
                'is_published' => true,
            ],
        );

        $questions = [
            [
                'type' => QuestionType::SingleChoice,
                'prompt' => 'A customer reports that an object has disappeared from their list. '
                    .'You can see it perfectly well from your own login. What does that tell you?',
                'explanation' => 'Support logins carry far more rights than a customer\'s. '
                    .'"It works for me" proves nothing — reproduce as the user, or read that user\'s rights directly.',
                'points' => 2,
                'options' => [
                    ['The object is fine, so the customer is mistaken', false],
                    ['Nothing yet — check that user\'s rights rather than your own view', true],
                    ['The device has stopped reporting', false],
                    ['The contract has been blocked for non-payment', false],
                ],
            ],
            [
                'type' => QuestionType::SingleChoice,
                'prompt' => 'Fuel readings are wrong. Sensors tracing shows the raw value arriving '
                    .'and it is correct. Where do you look next?',
                'explanation' => 'Raw value good means the device is fine — that is layer 6 ruled out. '
                    .'Work the configuration in order: field mapping, then the conversion formula, then the calibration table.',
                'points' => 2,
                'options' => [
                    ['The device — it needs replacing', false],
                    ['The calibration table', false],
                    ['The field mapping, then the formula, then the calibration table', true],
                    ['Nothing — escalate immediately', false],
                ],
            ],
            [
                'type' => QuestionType::MultipleChoice,
                'prompt' => 'A customer says they are not receiving notifications. '
                    .'Which of these are genuine causes worth checking? Select all that apply.',
                'explanation' => 'The time window is the single most-missed cause: a rule set for '
                    .'weekdays 09:00–18:00 is silent on a Saturday and looks broken.',
                'points' => 3,
                'options' => [
                    ['The Notifications module is not active on the contract', true],
                    ['The time window excludes the days the events happen', true],
                    ['The email address has never been verified', true],
                    ['The object was added after the notification was created', true],
                    ['The customer\'s vehicle is out of fuel', false],
                ],
            ],
            [
                'type' => QuestionType::TrueFalse,
                'prompt' => 'After correcting a calibration table, yesterday\'s report will show the '
                    .'corrected figures automatically.',
                'explanation' => 'Correcting configuration only changes new data. Historical reports '
                    .'stay wrong until you run Recalculate for the affected period — saying "fixed" without it earns a callback.',
                'points' => 2,
                'options' => [
                    ['True', false],
                    ['False', true],
                ],
            ],
            [
                'type' => QuestionType::SingleChoice,
                'prompt' => 'Three separate customers report the same symptom within an hour. '
                    .'What is the correct response?',
                'explanation' => 'Several customers with one symptom is an incident, not a ticket. '
                    .'Escalate immediately and stop debugging.',
                'points' => 2,
                'options' => [
                    ['Work each ticket individually to be thorough', false],
                    ['Escalate immediately as an incident and stop debugging', true],
                    ['Wait for a fourth report to confirm the pattern', false],
                    ['Close them as duplicates', false],
                ],
            ],
            [
                'type' => QuestionType::SingleChoice,
                'prompt' => 'A fleet manager phones and asks where a named driver was last Saturday. '
                    .'What do you do?',
                'explanation' => 'This puts you inside a possible employment dispute. They may well '
                    .'be entitled to it — but you are not the person who decides that. Route it, do not action it.',
                'points' => 2,
                'options' => [
                    ['Run the report — they are the account holder', false],
                    ['Refuse and end the call', false],
                    ['Route it per the data-protection policy rather than actioning it yourself', true],
                    ['Ask the driver for permission first', false],
                ],
            ],
            [
                'type' => QuestionType::ShortAnswer,
                'prompt' => 'In the layer model, which layer covers relay endpoints and the '
                    .'Queue / Err / Send / Ack statistics? Give the number.',
                'explanation' => 'Layer 7 — data reaching PILOT but not the customer\'s own system.',
                'points' => 1,
                'options' => [
                    ['7', true],
                    ['layer 7', true],
                    ['seven', true],
                ],
            ],
        ];

        foreach ($questions as $position => $questionData) {
            $question = QuizQuestion::query()->updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'prompt' => $questionData['prompt'],
                ],
                [
                    'type' => $questionData['type'],
                    'explanation' => $questionData['explanation'],
                    'points' => $questionData['points'],
                    'position' => $position + 1,
                ],
            );

            // Rebuilt rather than merged: option text is the identity here, and
            // a re-seed should not leave stale choices behind.
            $question->options()->delete();

            foreach ($questionData['options'] as $optionPosition => [$label, $isCorrect]) {
                $question->options()->create([
                    'label' => $label,
                    'is_correct' => $isCorrect,
                    'position' => $optionPosition + 1,
                ]);
            }
        }
    }
}
