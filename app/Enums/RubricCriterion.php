<?php

namespace App\Enums;

/**
 * The four criteria every practical task is scored against (§4.3).
 *
 * Fixed rather than configurable per task, and deliberately so: the point of a
 * standard rubric is that two trainers marking two different tasks are applying
 * the same standard. A per-task rubric would reintroduce exactly the drift the
 * calibration exercise exists to prevent.
 *
 * Each criterion is scored 0–4 against the descriptors below, which are shown to
 * the grader beside the score — a rubric you have to remember is not a rubric.
 */
enum RubricCriterion: string
{
    case Correctness = 'correctness';
    case Method = 'method';
    case Verification = 'verification';
    case Communication = 'communication';

    public const MAX_SCORE = 4;

    /** Total needed to pass, out of 16. */
    public const PASS_TOTAL = 10;

    /** Below this, the grader must say why. */
    public const COMMENT_REQUIRED_AT_OR_BELOW = 2;

    public function label(): string
    {
        return match ($this) {
            self::Correctness => 'Correctness',
            self::Method => 'Method / sequence',
            self::Verification => 'Verification',
            self::Communication => 'Communication',
        };
    }

    /**
     * Correctness carries a higher bar than the rest: a submission that reaches
     * the wrong answer tidily is still the wrong answer.
     */
    public function minimumToPass(): int
    {
        return $this === self::Correctness ? 3 : 2;
    }

    public function isCritical(): bool
    {
        return $this === self::Correctness;
    }

    /** The 0–4 descriptors, as they appear in the plan. */
    public function descriptor(int $score): string
    {
        return match ($this) {
            self::Correctness => match (true) {
                $score >= 4 => 'Correct, with edge cases handled',
                $score === 3 => 'Correct result',
                $score >= 1 => 'Partially correct; needed prompting',
                default => 'Result wrong or absent',
            },
            self::Method => match (true) {
                $score >= 4 => 'Order justified against the layer model',
                $score === 3 => 'Logical, efficient order',
                $score >= 1 => 'Steps out of order',
                default => 'No discernible order',
            },
            self::Verification => match (true) {
                $score >= 4 => 'Verified plus a negative check',
                $score === 3 => 'Verified and shown',
                $score >= 1 => 'Claimed without evidence',
                default => 'None',
            },
            self::Communication => match (true) {
                $score >= 4 => 'Explains to a non-technical audience',
                $score === 3 => 'Explains clearly',
                $score >= 1 => 'Explains with prompting',
                default => 'Cannot explain',
            },
        };
    }

    /** Every descriptor for one criterion, for rendering the full rubric. */
    public function descriptors(): array
    {
        return [
            0 => $this->descriptor(0),
            1 => $this->descriptor(1),
            2 => $this->descriptor(2),
            3 => $this->descriptor(3),
            4 => $this->descriptor(4),
        ];
    }

    public function position(): int
    {
        return match ($this) {
            self::Correctness => 1,
            self::Method => 2,
            self::Verification => 3,
            self::Communication => 4,
        };
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

    /** Highest total achievable — 4 criteria × 4 points. */
    public static function maxTotal(): int
    {
        return count(self::cases()) * self::MAX_SCORE;
    }
}
