<?php

namespace App\Actions\Progress;

use App\Enums\CompletionRequirement;
use App\Models\CourseProgress;
use App\Models\Topic;
use App\Models\TopicProgress;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Replaces `STATE[key] = !STATE[key]` from the prototype's tracker.
 */
class CompleteTopic
{
    public function __construct(
        private readonly RecalculateCourseProgress $recalculate,
    ) {}

    public function handle(User $user, Topic $topic): CourseProgress
    {
        // A topic gated on a quiz cannot be ticked off by hand. Without this,
        // the completion requirement would be advisory and an employee could
        // skip the assessment by posting to the completion endpoint.
        if ($topic->completion_requirement === CompletionRequirement::Quiz) {
            $quiz = $topic->quiz;

            if (! $quiz || ! $quiz->passedBy($user)) {
                throw ValidationException::withMessages([
                    'topic' => 'This topic is completed by passing its quiz.',
                ]);
            }
        }

        TopicProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
            ],
            [
                'course_id' => $topic->course_id,
                'completed_at' => now(),
                'last_viewed_at' => now(),
            ],
        );

        return $this->recalculate->handle($user, $topic->course);
    }

    /** Un-ticking an item — the prototype's toggle behaviour. */
    public function undo(User $user, Topic $topic): CourseProgress
    {
        TopicProgress::query()
            ->where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->update(['completed_at' => null]);

        return $this->recalculate->handle($user, $topic->course);
    }

    /** Records that the topic was opened, without marking it done. */
    public function touch(User $user, Topic $topic): void
    {
        $progress = TopicProgress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
            ],
            [
                'course_id' => $topic->course_id,
            ],
        );

        $progress->forceFill(['last_viewed_at' => now()])->save();

        // "Viewing it is enough" topics complete on open.
        if ($topic->completion_requirement === CompletionRequirement::View
            && $progress->completed_at === null) {
            $this->handle($user, $topic);
        }
    }
}
