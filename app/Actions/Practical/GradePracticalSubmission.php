<?php

namespace App\Actions\Practical;

use App\Enums\RubricCriterion;
use App\Enums\SubmissionStatus;
use App\Models\PracticalGrading;
use App\Models\PracticalSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marking a practical task against the four-criterion rubric (§4.3).
 *
 * Two rules are enforced here rather than in a form, so they hold whatever calls
 * this — the admin panel, a future API, a backfill:
 *
 *  - every criterion must be scored, 0 to 4;
 *  - a score of 2 or below needs a comment. "Why did this fail" is the only part
 *    of a rubric a trainee can learn from, and a bare 1 teaches nothing.
 */
class GradePracticalSubmission
{
    public function __construct(
        private readonly FinalisePracticalSubmission $finalise,
    ) {}

    /**
     * @param  array<string, int>  $scores  criterion value => 0–4
     * @param  array<string, string|null>  $comments  criterion value => comment
     */
    public function handle(
        PracticalSubmission $submission,
        User $grader,
        array $scores,
        array $comments = [],
        ?string $summary = null,
    ): PracticalGrading {
        $this->validate($scores, $comments);

        return DB::transaction(function () use ($submission, $grader, $scores, $comments, $summary): PracticalGrading {
            $total = PracticalGrading::total($scores);
            $passed = PracticalGrading::passes($scores);

            // updateOrCreate, so a grader revising their own marks does not
            // create a second opinion under their own name.
            $grading = PracticalGrading::query()->updateOrCreate(
                [
                    'practical_submission_id' => $submission->getKey(),
                    'grader_id' => $grader->getKey(),
                ],
                [
                    'total_score' => $total,
                    'passed' => $passed,
                    'summary' => $summary,
                    'graded_at' => now(),
                ],
            );

            foreach (RubricCriterion::cases() as $criterion) {
                $grading->scores()->updateOrCreate(
                    ['criterion' => $criterion->value],
                    [
                        'score' => $scores[$criterion->value],
                        'comment' => $comments[$criterion->value] ?? null,
                    ],
                );
            }

            $grading->load('scores');

            // Only settles the submission once it has the gradings it needs —
            // with a second marker required, one set of marks is an opinion.
            $this->finalise->handle($submission->refresh());

            return $grading->refresh();
        });
    }

    /**
     * Send it back for another go without recording a fail.
     *
     * Distinct from failing: "you did not include the verification screenshot"
     * is a gap in the evidence, not a judgement on the work.
     */
    public function returnForRevision(PracticalSubmission $submission, User $grader, string $reason): PracticalSubmission
    {
        abort_if(blank($reason), 422, 'Say what needs to change.');

        $submission->forceFill([
            'status' => SubmissionStatus::Returned,
            'returned_reason' => $reason,
        ])->save();

        return $submission->refresh();
    }

    /**
     * @param  array<string, int>  $scores
     * @param  array<string, string|null>  $comments
     */
    private function validate(array $scores, array $comments): void
    {
        $errors = [];

        foreach (RubricCriterion::cases() as $criterion) {
            $key = $criterion->value;
            $score = $scores[$key] ?? null;

            if ($score === null || ! is_numeric($score)) {
                $errors["scores.{$key}"] = [$criterion->label().' has not been scored.'];

                continue;
            }

            $score = (int) $score;

            if ($score < 0 || $score > RubricCriterion::MAX_SCORE) {
                $errors["scores.{$key}"] = [$criterion->label().' must be between 0 and '.RubricCriterion::MAX_SCORE.'.'];

                continue;
            }

            if ($score <= RubricCriterion::COMMENT_REQUIRED_AT_OR_BELOW && blank($comments[$key] ?? null)) {
                $errors["comments.{$key}"] = [
                    $criterion->label().' scored '.$score.' — say why, so they know what to fix.',
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
