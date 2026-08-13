<?php

namespace App\Models;

use App\Enums\EnrollmentSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'course_id',
    'source',
    'assignment_rule_id',
    'assigned_by',
    'enrolled_at',
    'due_at',
])]
class CourseEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'source' => EnrollmentSource::class,
            'enrolled_at' => 'datetime',
            'due_at' => 'datetime',
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

    public function assignmentRule(): BelongsTo
    {
        return $this->belongsTo(AssignmentRule::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_at')->where('due_at', '<', now());
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }
}
