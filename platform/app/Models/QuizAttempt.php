<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'quiz_id',
    'course_id',
    'attempt_number',
    'status',
    'score',
    'points_earned',
    'points_possible',
    'passed',
    'passing_score',
    'started_at',
    'completed_at',
])]
class QuizAttempt extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'score' => 'decimal:2',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AttemptStatus::Completed->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    /**
     * Whether the clock has run out. Enforced server-side at submission; the
     * countdown in the browser is a courtesy, not a control.
     */
    public function hasExpired(): bool
    {
        $limit = $this->quiz->time_limit_minutes;

        if (! $limit || ! $this->isInProgress()) {
            return false;
        }

        return $this->started_at->addMinutes($limit)->isPast();
    }
}
