<?php

namespace Database\Factories;

use App\Enums\CompletionRequirement;
use App\Enums\TopicType;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'lesson_id' => Lesson::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'type' => TopicType::RichText,
            'content' => '<p>'.fake()->paragraph().'</p>',
            'completion_requirement' => CompletionRequirement::View,
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
