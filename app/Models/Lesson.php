<?php

namespace App\Models;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Support\Video\VideoEmbed;
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
    'video_provider',
    'video_id',
    'video_duration_seconds',
    'video_transcript',
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

    /**
     * The parsed video reference, or null.
     *
     * Reads from the stored provider and id rather than any URL, so the embed
     * URL the view renders is always rebuilt from a fixed template.
     */
    public function videoEmbed(): ?VideoEmbed
    {
        if (! $this->video_provider || ! $this->video_id) {
            return null;
        }

        return VideoEmbed::parse($this->video_id, $this->video_provider);
    }

    /** "6:30" — how the duration reads next to the title. */
    public function videoDurationForHumans(): ?string
    {
        $seconds = (int) $this->video_duration_seconds;

        if ($seconds <= 0) {
            return null;
        }

        return intdiv($seconds, 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
    }

    /**
     * §4.1 sets a 5–7 minute ceiling: attention falls sharply past about six
     * minutes. Not enforced — a legitimate 9-minute walkthrough exists — but
     * flagged to the author and reportable in the content audit.
     */
    public function videoExceedsRecommendedLength(): bool
    {
        return (int) $this->video_duration_seconds > 7 * 60;
    }

    public function completedBy(User $user): bool
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->exists();
    }
}
