<?php

namespace App\Actions\Enrollment;

use App\Actions\Progress\RecalculateCourseProgress;
use App\Enums\EnrollmentSource;
use App\Models\AssignmentRule;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Notifications\CourseAssigned;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollEmployee
{
    public function __construct(
        private readonly RecalculateCourseProgress $recalculate,
    ) {}

    public function handle(
        User $user,
        Course $course,
        EnrollmentSource $source = EnrollmentSource::Manual,
        ?User $assignedBy = null,
        ?AssignmentRule $rule = null,
    ): CourseEnrollment {
        return DB::transaction(function () use ($user, $course, $source, $assignedBy, $rule): CourseEnrollment {
            // withTrashed: unenrolling soft-deletes, and the (user, course)
            // unique index still holds the slot. Re-assigning restores the
            // original row rather than failing on the constraint.
            $enrollment = CourseEnrollment::withTrashed()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first();

            $dueAt = $this->dueDate($rule, $course);

            if ($enrollment) {
                if ($enrollment->trashed()) {
                    $enrollment->restore();
                    $enrollment->forceFill([
                        'source' => $source,
                        'assignment_rule_id' => $rule?->id,
                        'assigned_by' => $assignedBy?->id,
                        'enrolled_at' => now(),
                        'due_at' => $dueAt,
                    ])->save();
                }

                return $enrollment;
            }

            $enrollment = CourseEnrollment::query()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'source' => $source,
                'assignment_rule_id' => $rule?->id,
                'assigned_by' => $assignedBy?->id,
                'enrolled_at' => now(),
                'due_at' => $dueAt,
            ]);

            // Create the rollup immediately so the dashboard can show
            // "not started" without a null check on every read.
            $this->recalculate->handle($user, $course);

            // Only on a genuinely new enrollment — restoring a revoked one, or
            // re-running an assignment rule, must not spam somebody who already
            // knows about the course.
            $user->notify(new CourseAssigned($course, $enrollment));

            return $enrollment;
        });
    }

    public function revoke(User $user, Course $course): void
    {
        CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->delete();
    }

    /**
     * The rule's own deadline wins over the course default, so "Operations get
     * 14 days for this one" does not need a separate course.
     */
    private function dueDate(?AssignmentRule $rule, Course $course): ?Carbon
    {
        $days = $rule?->due_days ?? $course->due_days;

        return $days ? now()->addDays($days) : null;
    }
}
