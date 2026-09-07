<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One pass/fail override, or one withdrawal of an override.
 *
 * Append-only by convention: nothing in the application updates or deletes a
 * row here. "Who overturned this, when, and why" has to survive the attempt
 * being re-graded afterwards.
 */
#[Fillable([
    'quiz_attempt_id',
    'trainee_id',
    'actor_id',
    'action',
    'from_passed',
    'to_passed',
    'score_at_override',
    'reason',
    'levels_revoked',
    'occurred_at',
])]
class GradeOverrideLog extends Model
{
    use HasFactory;

    public const ACTION_OVERRIDDEN = 'overridden';

    public const ACTION_WITHDRAWN = 'withdrawn';

    protected function casts(): array
    {
        return [
            'from_passed' => 'boolean',
            'to_passed' => 'boolean',
            'score_at_override' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** "Fail → Pass", as it reads in the audit table. */
    public function transition(): string
    {
        $label = fn (?bool $passed) => match ($passed) {
            true => 'Pass',
            false => 'Fail',
            null => 'Not marked',
        };

        return $label($this->from_passed).' → '.$label($this->to_passed);
    }
}
