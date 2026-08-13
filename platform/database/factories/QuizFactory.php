<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'course_module_id' => null,
            'lesson_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'passing_score' => 70,
            'max_attempts' => null,
            'time_limit_minutes' => null,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'show_feedback' => true,
            'is_published' => true,
        ];
    }

    public function withAttemptLimit(int $attempts): static
    {
        return $this->state(fn () => ['max_attempts' => $attempts]);
    }
}
