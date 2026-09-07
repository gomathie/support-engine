<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One attempt at a practical task.
 *
 * `total_score` and `passed` are the *reconciled* outcome and stay null until
 * enough gradings exist — with a second marker required, one trainer's marks are
 * an opinion, not a result.
 */
#[Fillable([
    'practical_task_id',
    'user_id',
    'attempt_number',
    'status',
    'body',
    'evidence',
    'submitted_at',
    'total_score',
    'passed',
    'finalised_at',
    'returned_reason',
])]
class PracticalSubmission extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'evidence' => 'array',
            'passed' => 'boolean',
            'submitted_at' => 'datetime',
            'finalised_at' => 'datetime',
        ];
    }

    public function scopeAwaitingMarking(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Submitted->value);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(PracticalTask::class, 'practical_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(PracticalSubmissionFile::class);
    }

    public function gradings(): HasMany
    {
        return $this->hasMany(PracticalGrading::class);
    }

    public function gradingBy(User $grader): ?PracticalGrading
    {
        return $this->gradings()->where('grader_id', $grader->getKey())->first();
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function awaitsMarking(): bool
    {
        return $this->status->awaitsMarking();
    }

    /** How many independent assessments this submission needs before it counts. */
    public function requiredGradings(): int
    {
        return $this->task?->requires_second_marker ? 2 : 1;
    }

    public function hasEnoughGradings(): bool
    {
        return $this->gradings()->count() >= $this->requiredGradings();
    }

    /**
     * Whether two markers disagreed on the outcome.
     *
     * This is what the calibration exercise is looking for — not a small
     * difference in totals, but one trainer passing what another failed.
     */
    public function markersDisagree(): bool
    {
        $verdicts = $this->gradings()->pluck('passed')->unique();

        return $verdicts->count() > 1;
    }

    /** The spread between the highest and lowest total awarded. */
    public function scoreSpread(): int
    {
        $totals = $this->gradings()->pluck('total_score');

        if ($totals->count() < 2) {
            return 0;
        }

        return (int) $totals->max() - (int) $totals->min();
    }
}
