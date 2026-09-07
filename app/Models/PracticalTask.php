<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A piece of work a trainee has to actually do, marked against the standard
 * four-criterion rubric.
 *
 * The thing a multiple-choice question cannot test: whether somebody can
 * configure the sensor, verify it, and explain what they did.
 */
#[Fillable([
    'course_id',
    'lesson_id',
    'title',
    'slug',
    'brief',
    'submission_instructions',
    'expected_evidence',
    'estimated_minutes',
    'position',
    'is_published',
    'requires_second_marker',
])]
class PracticalTask extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'requires_second_marker' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $task): void {
            $task->slug ??= Str::slug(Str::limit($task->title, 60, ''));

            // Denormalised the same way lessons are: a task attached to a
            // lesson must belong to that lesson's course.
            if ($task->lesson_id) {
                $lessonCourseId = Lesson::withTrashed()
                    ->whereKey($task->lesson_id)
                    ->value('course_id');

                if ($lessonCourseId) {
                    $task->course_id = $lessonCourseId;
                }
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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(PracticalSubmission::class);
    }

    /** This person's submissions, newest attempt first. */
    public function submissionsFor(User $user): HasMany
    {
        return $this->submissions()
            ->where('user_id', $user->getKey())
            ->orderByDesc('attempt_number');
    }

    public function latestSubmissionFor(User $user): ?PracticalSubmission
    {
        return $this->submissionsFor($user)->first();
    }

    public function passedBy(User $user): bool
    {
        return $this->submissions()
            ->where('user_id', $user->getKey())
            ->where('passed', true)
            ->exists();
    }
}
