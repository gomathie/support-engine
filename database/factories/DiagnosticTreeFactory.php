<?php

namespace Database\Factories;

use App\Models\DiagnosticTree;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DiagnosticTree>
 */
class DiagnosticTreeFactory extends Factory
{
    protected $model = DiagnosticTree::class;

    public function definition(): array
    {
        $question = fake()->unique()->sentence(5);

        return [
            'key' => Str::slug($question),
            'question' => $question,
            'layer_label' => '1–2',
            'description' => fake()->sentence(),
            'is_published' => true,
        ];
    }

    public function withSteps(int $count = 3): static
    {
        return $this->afterCreating(function (DiagnosticTree $tree) use ($count): void {
            for ($i = 0; $i < $count; $i++) {
                $tree->steps()->create([
                    'prompt' => fake()->sentence().'?',
                    'layer' => fake()->numberBetween(1, 7),
                    'fix' => fake()->sentence(),
                    'position' => $i + 1,
                ]);
            }
        });
    }
}
