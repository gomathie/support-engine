<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Multiple choice (one answer)',
            self::MultipleChoice => 'Multiple choice (several answers)',
            self::TrueFalse => 'True / false',
            self::ShortAnswer => 'Short answer',
        };
    }

    /**
     * Whether the employee submits option ids. Short answer submits free text,
     * which is matched against the accepted answers stored as options.
     */
    public function usesOptions(): bool
    {
        return $this !== self::ShortAnswer;
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
