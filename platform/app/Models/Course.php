<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'category',
    'summary',
    'description',
    'thumbnail_path',
    'instructor_id',
    'status',
    'difficulty',
    'estimated_minutes',
    'is_required',
    'due_days',
    'published_at',
    'created_by',
])]
class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'is_required' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $course): void {
            $course->slug ??= Str::slug($course->title);

            // published_at is the audit trail of when it went live; status is the
            // switch. Keep them consistent without making the author set both.
            if ($course->status === CourseStatus::Published && $course->published_at === null) {
                $course->published_at = now();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // --------------------------------------------------------------- scopes

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published->value);
    }

    /** Published or archived — i.e. anything an employee may legitimately open. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            CourseStatus::Published->value,
            CourseStatus::Archived->value,
        ]);
    }

    // --------------------------------------------------------- relationships

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * The course-level assessment: a quiz attached to the course but to no
     * particular module or lesson.
     */
    public function finalQuiz(): HasMany
    {
        return $this->hasMany(Quiz::class)
            ->whereNull('course_module_id')
            ->whereNull('lesson_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments')
            ->withPivot(['source', 'due_at', 'enrolled_at', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CourseProgress::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function assignmentRules(): HasMany
    {
        return $this->hasMany(AssignmentRule::class);
    }

    public function resources(): HasManyThrough
    {
        return $this->hasManyThrough(LessonResource::class, Lesson::class);
    }

    // ------------------------------------------------------------ accessors

    public function publishedLessonCount(): int
    {
        return $this->lessons()->where('is_published', true)->count();
    }

    public function isVisibleToEmployees(): bool
    {
        return $this->status->isVisibleToEmployees();
    }
}
