<?php

namespace App\Models;

use App\Enums\CompletionRequirement;
use App\Enums\TopicType;
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
    'lesson_id',
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
    'video_disk',
    'video_path',
    'video_original_name',
    'video_mime_type',
    'video_size_bytes',
    'video_status',
    'estimated_minutes',
    'completion_requirement',
    'position',
    'is_published',
])]
class Topic extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => TopicType::class,
            'completion_requirement' => CompletionRequirement::class,
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $topic): void {
            $topic->slug ??= Str::slug($topic->title);

            // course_id is denormalised for query speed, so it must never be
            // allowed to drift from the module's course.
            if ($topic->lesson_id) {
                $topic->course_id = Lesson::withTrashed()
                    ->whereKey($topic->lesson_id)
                    ->value('course_id');
            }
        });

        static::creating(function (self $topic): void {
            if ($topic->position === null || $topic->position === 0) {
                $topic->position = (int) static::query()
                    ->where('lesson_id', $topic->lesson_id)
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

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(TopicResource::class)->orderBy('position');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(TopicAnnotation::class)->orderBy('position');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TopicProgress::class);
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

    /** An uploaded video is playable once a file is actually on the disk. */
    public function hasUploadedVideo(): bool
    {
        return $this->type->isUploadedVideo() && filled($this->video_path);
    }

    /** "412 MB" — the size as the author needs to see it against the 500 MB cap. */
    public function videoSizeForHumans(): ?string
    {
        $bytes = (int) $this->video_size_bytes;

        if ($bytes <= 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $power > 1 ? 1 : 0).' '.$units[$power];
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
