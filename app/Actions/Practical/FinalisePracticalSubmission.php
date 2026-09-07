<?php

namespace App\Actions\Practical;

use App\Enums\SubmissionStatus;
use App\Models\PracticalSubmission;

/**
 * Turns one or two independent gradings into a single outcome.
 *
 * The reconciliation rule where two markers are required and they disagree:
 * **the submission does not settle.** It stays awaiting marking, flagged, until
 * the trainers reconcile. Averaging two verdicts would defeat the point of
 * double-marking — the disagreement is the signal §4.3 is asking for, and
 * silently splitting the difference would hide exactly the rubric drift the
 * calibration exists to catch.
 */
class FinalisePracticalSubmission
{
    public function handle(PracticalSubmission $submission): PracticalSubmission
    {
        $submission->load(['gradings', 'task']);

        if (! $submission->hasEnoughGradings()) {
            return $submission;
        }

        // Two markers, two verdicts: a human has to settle it.
        if ($submission->markersDisagree()) {
            return $submission;
        }

        $gradings = $submission->gradings;

        // Agreed verdict. The total is the mean where two marked it, because
        // the pass/fail is already settled and the number is only ever read as
        // feedback.
        $total = (int) round($gradings->avg('total_score'));

        $submission->forceFill([
            'status' => SubmissionStatus::Graded,
            'total_score' => $total,
            'passed' => (bool) $gradings->first()->passed,
            'finalised_at' => now(),
        ])->save();

        return $submission->refresh();
    }
}
