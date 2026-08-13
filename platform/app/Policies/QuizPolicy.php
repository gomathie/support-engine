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

        // Deliberately not a blanket bypass. Even an administrator must not be
        // able to read another employee's in-flight attempt through the
        // employee-facing routes, so viewAttempt() is excluded here and decides
        // for itself.
        if ($ability === 'viewAttempt') {
            return null;
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

    /** An attempt — including its answers and score — belongs to the person who sat it. */
    public function viewAttempt(User $user, QuizAttempt $attempt): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($attempt->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole(Role::Admin->value)) {
            return true;
        }

        // A manager sees results for their own departments only.
        if ($user->hasRole(Role::Manager->value)) {
            return in_array(
                $attempt->user->department_id,
                $user->visibleDepartmentIds(),
                true,
            );
        }

        return false;
    }

    public function submitAttempt(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id
            && $attempt->isInProgress()
            && $user->is_active;
    }

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
