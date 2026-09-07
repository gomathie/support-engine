<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseModule>
 */
class CourseModuleFactory extends Factory
{
    protected $model = CourseModule::class;

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
