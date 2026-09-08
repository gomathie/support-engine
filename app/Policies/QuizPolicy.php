<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

class QuizPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if (! $quiz->is_published) {
            return false;
        }

        return $user->can('view', $quiz->course);
    }

    /**
     * Starting an attempt needs more than access: the employee must be enrolled,
     * and must have attempts left. The attempt limit is enforced here rather
     * than by hiding the button.
     */
    public function attempt(User $user, Quiz $quiz): bool
    {
        if (! $this->view($user, $quiz)) {
            return false;
        }

        $enrolled = $quiz->course->enrollments()->where('user_id', $user->id)->exists();

        if (! $enrolled) {
            return false;
        }

        if ($quiz->isFinalAssessment() && ! $this->courseworkIsFinished($user, $quiz)) {
            return false;
        }

        return $quiz->hasAttemptsRemainingFor($user);
    }

    /**
     * A final exam opens last: every lesson read, every knowledge check passed.
     *
     * An examination is a summative test of the whole course. Sitting it with
     * half the material unread burns an attempt and tells nobody anything —
     * least of all the trainee, who then has one fewer try at the real thing.
     *
     * This was previously only a flag on the course page deciding whether to
     * render the button. Posting straight at the endpoint started an attempt
     * regardless, which is the difference between a courtesy and a control.
     *
     * The papers of one examination do not gate each other: Sections A, B and C
     * are peers, and requiring them in sequence would be an invention.
     */
    private function courseworkIsFinished(User $user, Quiz $quiz): bool
    {
        $course = $quiz->course;

        $publishedLessons = $course->lessons()->where('is_published', true)->pluck('id');

        if ($publishedLessons->isNotEmpty()) {
            $completed = $user->lessonProgress()
                ->whereIn('lesson_id', $publishedLessons)
                ->whereNotNull('completed_at')
                ->count();

            if ($completed < $publishedLessons->count()) {
                return false;
            }
        }

        // Module-scoped checks only. Lesson quizzes are already accounted for:
        // a lesson gated on one is not complete until it is passed.
        $checks = $course->quizzes()
            ->whereNotNull('module_id')
            ->whereNull('lesson_id')
            ->where('is_published', true)
            ->get();

        return $checks->every(fn (Quiz $check) => $check->passedBy($user));
    }

    /*
     * Abilities on a QuizAttempt live on QuizAttemptPolicy, not here. Laravel
     * resolves a policy by model name, so anything defined for an attempt on
     * this class would never be consulted.
     */


    /**
     * Reading the answer key. Never true for an employee — this guards the
     * admin-side question editor, not anything the taking-a-quiz screen touches.
     */
    public function viewAnswerKey(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quizzes.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quizzes.manage');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quizzes.manage');
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quizzes.manage');
    }
}
