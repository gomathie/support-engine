<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'category' => fake()->randomElement(['TRACK 1', 'TRACK 2', 'TRACK 3', 'PREP']),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => CourseStatus::Published,
            'difficulty' => 'beginner',
            'estimated_minutes' => fake()->numberBetween(30, 600),
            'is_required' => false,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn () => ['is_required' => true]);
    }
}
