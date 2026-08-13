<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * is_correct is hidden at the model level as a backstop. The employee-facing
 * payload is built by App\Http\Resources\QuizTakingResource, which selects only
 * id and label — but a stray ->toArray() elsewhere should not be able to leak
 * the answer key either.
 */
#[Fillable(['quiz_question_id', 'label', 'is_correct', 'position'])]
#[Hidden(['is_correct'])]
class QuizOption extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $option): void {
            if ($option->position === null || $option->position === 0) {
                $option->position = (int) static::query()
                    ->where('quiz_question_id', $option->quiz_question_id)
                    ->max('position') + 1;
            }
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
