<?php

namespace App\Actions\Progress;

use App\Enums\CompletionRequirement;
use App\Models\CourseProgress;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Replaces `STATE[key] = !STATE[key]` from the prototype's tracker.
 */
class CompleteLesson
{
    public function __construct(
        private readonly RecalculateCourseProgress $recalculate,
    ) {}

    public function handle(User $user, Lesson $lesson): CourseProgress
    {
        // A lesson gated on a quiz cannot be ticked off by hand. Without this,
        // the completion requirement would be advisory and an employee could
        // skip the assessment by posting to the completion endpoint.
        if ($lesson->completion_requirement === CompletionRequirement::Quiz) {
            $quiz = $lesson->quiz;

            if (! $quiz || ! $quiz->passedBy($user)) {
                throw ValidationException::withMessages([
                    'lesson' => 'This lesson is completed by passing its quiz.',
                ]);
            }
        }

        LessonProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'course_id' => $lesson->course_id,
                'completed_at' => now(),
                'last_viewed_at' => now(),
            ],
        );

        return $this->recalculate->handle($user, $lesson->course);
    }

    /** Un-ticking an item — the prototype's toggle behaviour. */
    public function undo(User $user, Lesson $lesson): CourseProgress
    {
        LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->update(['completed_at' => null]);

        return $this->recalculate->handle($user, $lesson->course);
    }

    /** Records that the lesson was opened, without marking it done. */
    public function touch(User $user, Lesson $lesson): void
    {
        $progress = LessonProgress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'course_id' => $lesson->course_id,
            ],
        );

        $progress->forceFill(['last_viewed_at' => now()])->save();

        // "Viewing it is enough" lessons complete on open.
        if ($lesson->completion_requirement === CompletionRequirement::View
            && $progress->completed_at === null) {
            $this->handle($user, $lesson);
        }
    }
}
