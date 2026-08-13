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
    'course_module_id',
    'lesson_id',
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

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
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

    /** A quiz tied to neither a module nor a lesson is the course's final test. */
    public function isFinalAssessment(): bool
    {
        return $this->course_module_id === null && $this->lesson_id === null;
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
