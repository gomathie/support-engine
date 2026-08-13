<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Models\QuizOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The attempt itself is authorized in the controller, once it has been
        // looked up and confirmed to belong to this quiz.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Quiz $quiz */
        $quiz = $this->route('quiz');

        $questionIds = $quiz->questions()->pluck('id')->all();

        return [
            'attempt_id' => ['required', 'integer'],

            'answers' => ['present', 'array'],

            // Only questions that belong to *this* quiz. Without this a
            // submission could name a question from another quiz and skew
            // the point total.
            'answers.*.question_id' => ['required', 'integer', Rule::in($questionIds)],

            'answers.*.option_ids' => ['sometimes', 'array'],
            'answers.*.option_ids.*' => [
                'integer',
                // And only options that belong to a question in this quiz.
                Rule::exists(QuizOption::class, 'id')->where(
                    fn ($query) => $query->whereIn('quiz_question_id', $questionIds),
                ),
            ],

            'answers.*.text' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Normalised shape for the grading action.
     *
     * @return array<int, array{question_id: int, option_ids: array<int, int>, text: string|null}>
     */
    public function submissions(): array
    {
        return collect($this->validated()['answers'] ?? [])
            ->map(fn (array $answer) => [
                'question_id' => (int) $answer['question_id'],
                'option_ids' => array_map('intval', $answer['option_ids'] ?? []),
                'text' => $answer['text'] ?? null,
            ])
            ->all();
    }
}
