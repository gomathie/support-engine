<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidence that a person reached a rung in an area.
 *
 * `quiz_attempt_id` is the trail — which sitting earned it. `awarded_by` is
 * null when the system awarded it on completion, and set when a trainer or
 * admin granted it by hand.
 *
 * Withdrawn awards are revoked, never deleted: "she held Level 1 until March"
 * is a fact worth keeping.
 */
#[Fillable([
    'user_id',
    'level_id',
    'competency_area_id',
    'awarded_at',
    'awarded_by',
    'quiz_attempt_id',
    'revoked_at',
    'revoked_by',
    'revoked_reason',
])]
class TraineeLevel extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function competencyArea(): BelongsTo
    {
        return $this->belongsTo(CompetencyArea::class);
    }

    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
