<?php

namespace App\Models;

use App\Enums\RubricCriterion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One criterion of one grading: a 0-4 score and, where the score is low, the
 * comment explaining it.
 */
#[Fillable([
    'practical_grading_id',
    'criterion',
    'score',
    'comment',
])]
class PracticalGradingScore extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'criterion' => RubricCriterion::class,
            'score' => 'integer',
        ];
    }

    public function grading(): BelongsTo
    {
        return $this->belongsTo(PracticalGrading::class, 'practical_grading_id');
    }

    /** The rubric wording for the score awarded, shown beside it. */
    public function descriptor(): string
    {
        return $this->criterion->descriptor($this->score);
    }

    public function needsComment(): bool
    {
        return $this->score <= RubricCriterion::COMMENT_REQUIRED_AT_OR_BELOW;
    }

    public function meetsStandard(): bool
    {
        return $this->score >= $this->criterion->minimumToPass();
    }
}
