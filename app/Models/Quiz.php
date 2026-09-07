<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'course_id',
    'lesson_id',
    'topic_id',
    'title',
    'description',
    'passing_score',
    'max_attempts',
    'time_limit_minutes',
    'shuffle_questions',
    'shuffle_options',
    'show_feedback',
    'is_published',
])]
class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * What the quiz is attached to. Derived from lesson_id and
     * topic_id rather than stored, so the two cannot drift apart.
     */
    public const SCOPE_FINAL = 'final';

    public const SCOPE_LESSON = 'lesson';

    public const SCOPE_TOPIC = 'topic';

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'max_attempts' => 'integer',
            'time_limit_minutes' => 'integer',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_feedback' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    // --------------------------------------------------------- relationships

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // ------------------------------------------------------------- helpers

    /** A quiz tied to neither a module nor a topic is the course's final test. */
    public function isFinalAssessment(): bool
    {
        return $this->lesson_id === null && $this->topic_id === null;
    }

    public function scope(): string
    {
        return match (true) {
            $this->topic_id !== null => self::SCOPE_TOPIC,
            $this->lesson_id !== null => self::SCOPE_LESSON,
            default => self::SCOPE_FINAL,
        };
    }

    public function scopeLabel(): string
    {
        return match ($this->scope()) {
            self::SCOPE_TOPIC => 'Topic check',
            self::SCOPE_LESSON => 'Knowledge check',
            default => 'Final exam',
        };
    }

    public function totalPoints(): int
    {
        return (int) $this->questions()->sum('points');
    }

    public function attemptsUsedBy(User $user): int
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->where('status', '!=', AttemptStatus::Abandoned->value)
            ->count();
    }

    public function hasAttemptsRemainingFor(User $user): bool
    {
        if ($this->max_attempts === null) {
            return true;
        }

        return $this->attemptsUsedBy($user) < $this->max_attempts;
    }

    public function bestAttemptFor(User $user): ?QuizAttempt
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed->value)
            ->orderByDesc('score')
            ->first();
    }

    public function passedBy(User $user): bool
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->exists();
    }
}
