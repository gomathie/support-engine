<?php

namespace App\Models;

use App\Enums\RefresherStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A five-question check that somebody still knows what they were signed off on.
 *
 * Scheduled at 30 and 90 days from the moment a level is awarded, drawn from
 * that level's own question bank. It grants nothing and gates nothing — the
 * level is already held, and a poor refresher does not take it away. What it
 * produces is a number: whether the training stuck, which is KPI 6 and the one
 * thing nothing in the product could measure before.
 */
#[Fillable([
    'user_id',
    'trainee_level_id',
    'interval_days',
    'due_at',
    'baseline_score',
    'status',
    'score',
    'retention',
    'started_at',
    'completed_at',
])]
class Refresher extends Model
{
    use HasFactory;

    /** §2: "a 5-question refresher at 30 and 90 days". */
    public const QUESTION_COUNT = 5;

    /** @var array<int, int> */
    public const INTERVALS = [30, 90];

    /**
     * How long a refresher stays open after it falls due.
     *
     * Long enough to survive a fortnight's leave, short enough that a "90-day"
     * retention figure still means roughly ninety days. Past it, the refresher
     * is Missed rather than sat late against a stale question bank.
     */
    public const WINDOW_DAYS = 21;

    protected function casts(): array
    {
        return [
            'status' => RefresherStatus::class,
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'baseline_score' => 'decimal:2',
            'score' => 'decimal:2',
            'retention' => 'decimal:2',
            'interval_days' => 'integer',
        ];
    }

    // --------------------------------------------------------- relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function traineeLevel(): BelongsTo
    {
        return $this->belongsTo(TraineeLevel::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RefresherAnswer::class)->orderBy('position');
    }

    // ------------------------------------------------------------- scopes

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', RefresherStatus::Scheduled->value)
            ->where('due_at', '<=', now());
    }

    /** Due, and still inside the window in which it may be sat. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->due()
            ->where('due_at', '>', now()->subDays(self::WINDOW_DAYS));
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', RefresherStatus::Completed->value);
    }

    // ------------------------------------------------------------- helpers

    public function isDue(): bool
    {
        return $this->status === RefresherStatus::Scheduled && $this->due_at->isPast();
    }

    public function closesAt(): \Illuminate\Support\Carbon
    {
        return $this->due_at->copy()->addDays(self::WINDOW_DAYS);
    }

    /** Due and not yet closed — the only state in which it can be sat. */
    public function isOpen(): bool
    {
        return $this->isDue() && $this->closesAt()->isFuture();
    }

    public function hasLapsed(): bool
    {
        return $this->status === RefresherStatus::Scheduled && $this->closesAt()->isPast();
    }

    /**
     * Retention against the original sitting, as a percentage of it.
     *
     * Null when there is no baseline — a hand-granted level has no exam score
     * behind it, and a comparison against nothing is not a zero.
     */
    public function retentionAgainstBaseline(): ?float
    {
        $baseline = (float) $this->baseline_score;

        if ($baseline <= 0 || $this->score === null) {
            return null;
        }

        return round(((float) $this->score / $baseline) * 100, 2);
    }

    /** "30-day refresher" — how it reads in a list. */
    public function label(): string
    {
        return $this->interval_days.'-day refresher';
    }
}
