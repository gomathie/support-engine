<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quiz_attempt_id',
    'quiz_question_id',
    'selected_option_ids',
    'text_answer',
    'is_correct',
    'points_awarded',
    'graded_at',
    'graded_by',
    'grader_feedback',
    'answered_at',
])]
class QuizAnswer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /** Written answers sit unmarked until an examiner reads them. */
    public function awaitsGrading(): bool
    {
        return $this->graded_at === null;
    }
}
