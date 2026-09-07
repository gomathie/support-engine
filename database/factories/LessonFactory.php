<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => 'Day '.fake()->numberBetween(1, 14),
            'subtitle' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'is_published' => true,
        ];
    }
}
