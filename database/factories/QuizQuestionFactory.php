<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::SingleChoice,
            'prompt' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
            'points' => 1,
        ];
    }

    /**
     * Adds options after creation. The first is correct unless told otherwise.
     */
    public function withOptions(int $count = 4, array $correctIndexes = [0]): static
    {
        return $this->afterCreating(function (QuizQuestion $question) use ($count, $correctIndexes): void {
            for ($i = 0; $i < $count; $i++) {
                $question->options()->create([
                    'label' => 'Option '.($i + 1),
                    'is_correct' => in_array($i, $correctIndexes, true),
                    'position' => $i + 1,
                ]);
            }
        });
    }

    public function multipleChoice(): static
    {
        return $this->state(fn () => ['type' => QuestionType::MultipleChoice]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn () => ['type' => QuestionType::ShortAnswer]);
    }
}
