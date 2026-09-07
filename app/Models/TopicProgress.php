<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'topic_id',
    'course_id',
    'completed_at',
    'time_spent_seconds',
    'last_viewed_at',
])]
class TopicProgress extends Model
{
    use HasFactory;

    protected $table = 'topic_progress';

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'time_spent_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
