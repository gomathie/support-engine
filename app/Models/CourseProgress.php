<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'course_id',
    'status',
    'completed_lessons',
    'total_lessons',
    'percentage',
    'final_score',
    'quiz_attempts_count',
    'started_at',
    'completed_at',
    'last_activity_at',
])]
class CourseProgress extends Model
{
    use HasFactory;

    protected $table = 'course_progress';

    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'percentage' => 'decimal:2',
            'final_score' => 'decimal:2',
            'completed_lessons' => 'integer',
            'total_lessons' => 'integer',
            'quiz_attempts_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ProgressStatus::Completed->value);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', ProgressStatus::InProgress->value);
    }

    public function isComplete(): bool
    {
        return $this->status === ProgressStatus::Completed;
    }
}
