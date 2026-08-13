<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    /**
     * Long-form. There is no set of accepted answers to match against — an
     * examiner reads it and awards points out of the question's maximum.
     */
    case Written = 'written';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Multiple choice (one answer)',
            self::MultipleChoice => 'Multiple choice (several answers)',
            self::TrueFalse => 'True / false',
            self::ShortAnswer => 'Short answer (auto-marked)',
            self::Written => 'Written answer (marked by an examiner)',
        };
    }

    /**
     * Whether the employee submits option ids. Short and written answers submit
     * free text; short answer is matched against accepted answers stored as
     * options, written answer is read by a person.
     */
    public function usesOptions(): bool
    {
        return ! in_array($this, [self::ShortAnswer, self::Written], true);
    }

    /** Whether a human has to mark it before the attempt can be scored. */
    public function requiresManualGrading(): bool
    {
        return $this === self::Written;
    }

    public function allowsMultipleSelections(): bool
    {
        return $this === self::MultipleChoice;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
