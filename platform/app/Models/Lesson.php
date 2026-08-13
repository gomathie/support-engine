<?php

namespace App\Models;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'course_module_id',
    'course_id',
    'title',
    'slug',
    'description',
    'type',
    'content',
    'external_url',
    'estimated_minutes',
    'completion_requirement',
    'position',
    'is_published',
])]
class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'completion_requirement' => CompletionRequirement::class,
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $lesson): void {
            $lesson->slug ??= Str::slug($lesson->title);

            // course_id is denormalised for query speed, so it must never be
            // allowed to drift from the module's course.
            if ($lesson->course_module_id) {
                $lesson->course_id = CourseModule::withTrashed()
                    ->whereKey($lesson->course_module_id)
                    ->value('course_id');
            }
        });

        static::creating(function (self $lesson): void {
            if ($lesson->position === null || $lesson->position === 0) {
                $lesson->position = (int) static::query()
                    ->where('course_module_id', $lesson->course_module_id)
                    ->max('position') + 1;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    // --------------------------------------------------------- relationships

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class)->orderBy('position');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(LessonAnnotation::class)->orderBy('position');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    // ------------------------------------------------------------- helpers

    public function requiresQuiz(): bool
    {
        return $this->completion_requirement === CompletionRequirement::Quiz;
    }

    public function completedBy(User $user): bool
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->exists();
    }
}
