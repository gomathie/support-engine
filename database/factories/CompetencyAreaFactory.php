<?php

namespace Database\Factories;

use App\Models\CompetencyArea;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CompetencyArea>
 */
class CompetencyAreaFactory extends Factory
{
    protected $model = CompetencyArea::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'position' => fake()->numberBetween(1, 20),
        ];
    }
}
