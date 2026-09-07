<?php

namespace App\Models;

use App\Enums\RubricCriterion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * One trainer's assessment of one submission, against the four criteria.
 *
 * Stored per grader rather than as columns on the submission, because §4.3 wants
 * the first submissions marked independently by two trainers and then
 * reconciled. Independent means neither sees the other's marks first — which is
 * only possible if they are separate rows.
 */
#[Fillable([
    'practical_submission_id',
    'grader_id',
    'total_score',
    'passed',
    'summary',
    'graded_at',
])]
class PracticalGrading extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'graded_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(PracticalSubmission::class, 'practical_submission_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grader_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PracticalGradingScore::class);
    }

    /** @return Collection<string, PracticalGradingScore> keyed by criterion value */
    public function scoresByCriterion(): Collection
    {
        return $this->scores->keyBy(fn (PracticalGradingScore $score) => $score->criterion->value);
    }

    public function scoreFor(RubricCriterion $criterion): ?int
    {
        $score = $this->scores->firstWhere('criterion', $criterion);

        return $score?->score;
    }

    /**
     * The §4.3 threshold, in one place.
     *
     * Three conditions, all of which must hold: at least 3 on Correctness, at
     * least 2 on every other criterion, and 10 or more overall. A submission can
     * clear the total and still fail — 4/4/2/0 sums to 10 but cannot explain
     * itself, and that is not a pass.
     *
     * @param  array<string, int>  $scores  criterion value => 0–4
     */
    public static function passes(array $scores): bool
    {
        $total = 0;

        foreach (RubricCriterion::cases() as $criterion) {
            $score = $scores[$criterion->value] ?? null;

            if ($score === null || $score < $criterion->minimumToPass()) {
                return false;
            }

            $total += $score;
        }

        return $total >= RubricCriterion::PASS_TOTAL;
    }

    /** @param  array<string, int>  $scores */
    public static function total(array $scores): int
    {
        return array_sum(array_map(
            fn (RubricCriterion $c) => $scores[$c->value] ?? 0,
            RubricCriterion::cases(),
        ));
    }

    /** Why this passed or failed, in the words the trainee needs. */
    public function verdictExplanation(): string
    {
        if ($this->passed) {
            return 'Met the standard on all four criteria, '.$this->total_score.'/'.RubricCriterion::maxTotal().'.';
        }

        $shortfalls = [];

        foreach ($this->scores as $score) {
            if ($score->score < $score->criterion->minimumToPass()) {
                $shortfalls[] = $score->criterion->label()
                    .' ('.$score->score.', needs '.$score->criterion->minimumToPass().')';
            }
        }

        if ($shortfalls === []) {
            return 'Total of '.$this->total_score.'/'.RubricCriterion::maxTotal()
                .' is below the '.RubricCriterion::PASS_TOTAL.' needed to pass.';
        }

        return 'Below the standard on: '.implode(' · ', $shortfalls).'.';
    }
}
