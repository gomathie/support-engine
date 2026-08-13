<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'target_type',
    'target_id',
    'target_value',
    'due_days',
    'is_active',
    'applies_retroactively',
    'created_by',
])]
class AssignmentRule extends Model
{
    use HasFactory;

    public const TARGET_DEPARTMENT = 'department';

    public const TARGET_ROLE = 'role';

    public const TARGET_USER = 'user';

    public const TARGET_ALL = 'all';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'applies_retroactively' => 'boolean',
            'due_days' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The users this rule currently selects. Evaluated live rather than stored,
     * so moving somebody between departments changes what they are assigned
     * without anyone re-running anything.
     */
    public function matchingUsers(): Builder
    {
        $query = User::query()->where('is_active', true);

        return match ($this->target_type) {
            self::TARGET_DEPARTMENT => $query->where('department_id', $this->target_id),
            self::TARGET_ROLE => $query->role($this->target_value),
            self::TARGET_USER => $query->whereKey($this->target_id),
            default => $query,
        };
    }

    public function describeTarget(): string
    {
        return match ($this->target_type) {
            self::TARGET_DEPARTMENT => 'Department: '.($this->department?->name ?? 'unknown'),
            self::TARGET_ROLE => 'Role: '.$this->target_value,
            self::TARGET_USER => 'Employee: '.($this->targetUser?->name ?? 'unknown'),
            default => 'Everyone',
        };
    }
}
