<?php

namespace App\Actions\Practical;

use App\Enums\SubmissionStatus;
use App\Models\PracticalSubmission;
use App\Models\PracticalTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A trainee starting, saving and handing in a practical task.
 *
 * A returned submission is revised in place rather than becoming a new attempt:
 * "do it again with the verification step" is the same piece of work, and
 * counting it as attempt 2 would make the retry statistics meaningless. A fail
 * is what starts a fresh attempt.
 */
class SubmitPracticalTask
{
    /** The submission being worked on, created on first visit. */
    public function draftFor(User $user, PracticalTask $task): PracticalSubmission
    {
        return DB::transaction(function () use ($user, $task): PracticalSubmission {
            $open = $task->submissions()
                ->where('user_id', $user->getKey())
                ->whereIn('status', [SubmissionStatus::Draft->value, SubmissionStatus::Returned->value])
                ->lockForUpdate()
                ->orderByDesc('attempt_number')
                ->first();

            if ($open) {
                return $open;
            }

            $next = (int) $task->submissions()
                ->where('user_id', $user->getKey())
                ->max('attempt_number') + 1;

            return $task->submissions()->create([
                'user_id' => $user->getKey(),
                'attempt_number' => $next,
                'status' => SubmissionStatus::Draft,
            ]);
        });
    }

    public function saveDraft(PracticalSubmission $submission, string $body): PracticalSubmission
    {
        abort_unless($submission->isEditable(), 403, 'This submission has been handed in.');

        $submission->forceFill(['body' => $body])->save();

        return $submission->refresh();
    }

    /**
     * Hand it in.
     *
     * Clears any previous return reason: once it is back with the grader, the
     * old "needs the verification step" note is history, not a current state.
     */
    public function submit(PracticalSubmission $submission, ?string $body = null): PracticalSubmission
    {
        abort_unless($submission->isEditable(), 403, 'This submission has already been handed in.');

        $body ??= $submission->body;

        abort_if(blank($body), 422, 'Write up what you did before handing it in.');

        $submission->forceFill([
            'body' => $body,
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
            'returned_reason' => null,
        ])->save();

        return $submission->refresh();
    }
}
