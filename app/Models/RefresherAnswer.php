<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One question put in front of somebody on a refresher, and what they said.
 *
 * The row is created when the refresher is started, before anything is
 * answered — so the five questions are fixed at the moment of sitting rather
 * than re-drawn on submission, and an unanswered question is recorded as
 * unanswered instead of vanishing.
 */
#[Fillable([
    'refresher_id',
    'quiz_question_id',
    'selected_option_ids',
    'is_correct',
    'points_awarded',
    'position',
    'answered_at',
])]
class RefresherAnswer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function refresher(): BelongsTo
    {
        return $this->belongsTo(Refresher::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
