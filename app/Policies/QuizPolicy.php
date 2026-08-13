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

        return $quiz->hasAttemptsRemainingFor($user);
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
