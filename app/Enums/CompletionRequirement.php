<?php

namespace App\Enums;

/**
 * What it takes to finish one lesson.
 *
 * Read this alongside the course-level rule in RecalculateCourseProgress, because
 * the division of labour between them is the whole point:
 *
 *   A finished lesson is a **reading record**, not a claim of competence.
 *
 * Competence is decided by the final exam and the practical task, both of which
 * are marked by somebody other than the learner. That is why View is the sensible
 * default: it records honestly that the material was opened, and claims nothing
 * more. Self-attestation used to be the default and was doing the opposite —
 * letting a trainee assert they had learned something, and having the platform
 * report that assertion as progress.
 */
enum CompletionRequirement: string
{
    /** Opening the lesson records that it was read. */
    case View = 'view';

    /**
     * The learner ticks it off themselves.
     *
     * Kept for genuine attestations — "I have read the data protection policy"
     * is a record worth having, and only the person can make it. It is *not*
     * evidence that anything was learned, and it should not be used for
     * training content. Retired as the default in September 2026.
     */
    case Acknowledge = 'acknowledge';

    /** The attached quiz has to be passed. */
    case Quiz = 'quiz';

    /** The attached practical task has to be passed, marked by a trainer. */
    case Task = 'task';

    public function label(): string
    {
        return match ($this) {
            self::View => 'Reading it is recorded',
            self::Acknowledge => 'Learner attests to it (policy sign-off only)',
            self::Quiz => 'Attached quiz must be passed',
            self::Task => 'Attached practical task must be passed',
        };
    }

    /**
     * Whether finishing this lesson required somebody other than the learner to
     * agree. Only these two are evidence of anything.
     */
    public function isAssessed(): bool
    {
        return in_array($this, [self::Quiz, self::Task], true);
    }

    /** Whether the learner decides for themselves that it is done. */
    public function isSelfAttested(): bool
    {
        return $this === self::Acknowledge;
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
