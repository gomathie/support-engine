<?php

namespace Database\Factories;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'course_module_id' => CourseModule::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'type' => LessonType::RichText,
            'content' => '<p>'.fake()->paragraph().'</p>',
            'completion_requirement' => CompletionRequirement::Acknowledge,
            'estimated_minutes' => fake()->numberBetween(5, 60),
            'is_published' => true,
        ];
    }

    public function requiresQuiz(): static
    {
        return $this->state(fn () => [
            'completion_requirement' => CompletionRequirement::Quiz,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
