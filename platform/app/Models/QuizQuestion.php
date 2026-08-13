<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['quiz_id', 'type', 'prompt', 'explanation', 'points', 'position'])]
class QuizQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'points' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $question): void {
            if ($question->position === null || $question->position === 0) {
                $question->position = (int) static::query()
                    ->where('quiz_id', $question->quiz_id)
                    ->max('position') + 1;
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class)->orderBy('position');
    }

    /**
     * @return Collection<int, QuizOption>
     */
    public function correctOptions(): Collection
    {
        return $this->options()->where('is_correct', true)->get();
    }

    /** @return array<int, int> */
    public function correctOptionIds(): array
    {
        return $this->options()
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
