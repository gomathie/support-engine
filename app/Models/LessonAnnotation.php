<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lesson_id',
    'type',
    'anchor',
    'section_label',
    'body',
    'is_resolved',
    'position',
])]
class LessonAnnotation extends Model
{
    use HasFactory;

    public const TYPE_STANDARD_DEFAULT = 'standard_default';

    public const TYPE_GAP = 'gap';

    public const TYPE_NOTE = 'note';

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeStandardDefaults(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_STANDARD_DEFAULT);
    }

    public function scopeGaps(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_GAP);
    }
}
