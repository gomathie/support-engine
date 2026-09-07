<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),

            // Unique column, and the ladder order is what most tests assert on,
            // so it is usually passed explicitly.
            'position' => fake()->unique()->numberBetween(1, 200),
        ];
    }

    public function at(int $position): static
    {
        return $this->state(fn () => ['position' => $position]);
    }
}
