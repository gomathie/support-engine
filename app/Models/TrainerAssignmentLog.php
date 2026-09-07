<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change of trainer: who moved, from whom, to whom, by whose hand and why.
 *
 * Append-only by convention — nothing in the application updates or deletes a
 * row here. An assignment is the provenance of a grade, so "who held this cohort
 * in March" has to survive every later reassignment.
 */
#[Fillable([
    'trainee_id',
    'from_trainer_id',
    'to_trainer_id',
    'actor_id',
    'action',
    'reason',
    'pending_grading_inherited',
    'occurred_at',
])]
class TrainerAssignmentLog extends Model
{
    use HasFactory;

    public const ACTION_ASSIGNED = 'assigned';

    public const ACTION_REASSIGNED = 'reassigned';

    public const ACTION_UNASSIGNED = 'unassigned';

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'pending_grading_inherited' => 'integer',
        ];
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function fromTrainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_trainer_id');
    }

    public function toTrainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_trainer_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** "Aisha → Marek", as it reads in the audit table. */
    public function transition(): string
    {
        return ($this->fromTrainer?->name ?? 'Unassigned')
            .' → '
            .($this->toTrainer?->name ?? 'Unassigned');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_ASSIGNED => 'Assigned',
            self::ACTION_REASSIGNED => 'Reassigned',
            self::ACTION_UNASSIGNED => 'Unassigned',
            default => ucfirst((string) $this->action),
        };
    }
}
