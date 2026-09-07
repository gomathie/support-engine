<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One course that a given (level, area) pair demands.
 *
 * A level is earned by finishing a *set* of courses, so the requirement is a
 * row per course rather than a column on the level.
 */
#[Fillable(['level_id', 'competency_area_id', 'course_id'])]
class LevelRequirement extends Model
{
    use HasFactory;

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function competencyArea(): BelongsTo
    {
        return $this->belongsTo(CompetencyArea::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
