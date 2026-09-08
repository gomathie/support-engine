<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['course_id', 'title', 'subtitle', 'description', 'docs_reference', 'position', 'is_published'])]
class Module extends Model
{
    /**
     * The knowledge check at the end of this lesson.
     *
     * Module-scoped rather than lesson-scoped: one check covering the lesson's
     * lessons, sat once, rather than a question after every page.
     */
    public function knowledgeCheck(): ?Quiz
    {
        return Quiz::query()
            ->where('module_id', $this->getKey())
            ->whereNull('lesson_id')
            ->first();
    }

    /**
     * Every lesson is supposed to end with one, and passing it is required to
     * finish the course. A lesson without one can be read and left, so the gap
     * is worth seeing rather than discovering later.
     */
    public function hasKnowledgeCheck(): bool
    {
        return Quiz::query()
            ->where('module_id', $this->getKey())
            ->whereNull('lesson_id')
            ->where('is_published', true)
            ->exists();
    }

    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Append rather than collide on 0 when an author adds a module without
        // choosing a position.
        static::creating(function (self $module): void {
            if ($module->position === null || $module->position === 0) {
                $module->position = (int) static::query()
                    ->where('course_id', $module->course_id)
                    ->max('position') + 1;
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}
