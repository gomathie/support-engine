<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Enrollment\SyncAssignmentRules;
use App\Enums\EnrollmentSource;
use App\Enums\Role;
use App\Models\AssignmentRule;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolling_creates_a_progress_rollup(): void
    {
        $course = Course::factory()->create();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $this->assertDatabaseHas('course_progress', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'not_started',
        ]);
    }

    public function test_enrolling_twice_does_not_duplicate(): void
    {
        $course = Course::factory()->create();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);
        app(EnrollEmployee::class)->handle($user, $course);

        $this->assertSame(1, CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->count());
    }

    /**
     * Unenrolling soft-deletes, and the (user, course) unique index still holds
     * the slot — so re-assigning has to restore rather than insert.
     */
    public function test_re_enrolling_restores_a_revoked_enrollment(): void
    {
        $course = Course::factory()->create();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);
        app(EnrollEmployee::class)->revoke($user, $course);

        $this->assertSoftDeleted('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        app(EnrollEmployee::class)->handle($user, $course);

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'deleted_at' => null,
        ]);
    }

    public function test_a_due_date_is_derived_from_the_course(): void
    {
        $course = Course::factory()->create(['due_days' => 14]);
        $user = $this->employee();

        $enrollment = app(EnrollEmployee::class)->handle($user, $course);

        $this->assertNotNull($enrollment->due_at);
        $this->assertEqualsWithDelta(
            now()->addDays(14)->timestamp,
            $enrollment->due_at->timestamp,

            // Generous: the due date is set when the enrollment is created and
            // compared when the assertion runs, and a full suite takes minutes.
            // The point is "the right number of days out", not the second.
            120,
        );
    }

    // ─── ASSIGNMENT RULES ────────────────────────────────────

    public function test_a_department_rule_enrolls_everybody_in_it(): void
    {
        $department = Department::factory()->create();
        $course = Course::factory()->create();

        $inside = collect(range(1, 3))->map(fn () => $this->employee($department));
        $outside = $this->employee(Department::factory()->create());

        AssignmentRule::query()->create([
            'course_id' => $course->id,
            'target_type' => AssignmentRule::TARGET_DEPARTMENT,
            'target_id' => $department->id,
            'is_active' => true,
        ]);

        app(SyncAssignmentRules::class)->all();

        foreach ($inside as $user) {
            $this->assertDatabaseHas('course_enrollments', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'source' => EnrollmentSource::Rule->value,
            ]);
        }

        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $outside->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_a_role_rule_enrolls_by_role(): void
    {
        $course = Course::factory()->create();

        $employee = $this->employee();
        $admin = $this->admin();

        AssignmentRule::query()->create([
            'course_id' => $course->id,
            'target_type' => AssignmentRule::TARGET_ROLE,
            'target_value' => Role::Employee->value,
            'is_active' => true,
        ]);

        app(SyncAssignmentRules::class)->all();

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $employee->id,
            'course_id' => $course->id,
        ]);

        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $admin->id,
            'course_id' => $course->id,
        ]);
    }

    /**
     * Moving somebody between departments should pick up their new
     * department's training without an administrator remembering to assign it.
     */
    public function test_syncing_one_user_picks_up_their_departments_rules(): void
    {
        $operations = Department::factory()->create();
        $course = Course::factory()->create();

        AssignmentRule::query()->create([
            'course_id' => $course->id,
            'target_type' => AssignmentRule::TARGET_DEPARTMENT,
            'target_id' => $operations->id,
            'is_active' => true,
        ]);

        $user = $this->employee(Department::factory()->create());

        app(SyncAssignmentRules::class)->forUser($user);
        $this->assertDatabaseMissing('course_enrollments', ['user_id' => $user->id]);

        $user->update(['department_id' => $operations->id]);

        app(SyncAssignmentRules::class)->forUser($user->fresh());

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_an_inactive_rule_enrolls_nobody(): void
    {
        $department = Department::factory()->create();
        $course = Course::factory()->create();
        $user = $this->employee($department);

        AssignmentRule::query()->create([
            'course_id' => $course->id,
            'target_type' => AssignmentRule::TARGET_DEPARTMENT,
            'target_id' => $department->id,
            'is_active' => false,
        ]);

        app(SyncAssignmentRules::class)->all();

        $this->assertDatabaseMissing('course_enrollments', ['user_id' => $user->id]);
    }

    public function test_a_rules_due_date_overrides_the_course_default(): void
    {
        $department = Department::factory()->create();
        $course = Course::factory()->create(['due_days' => 30]);
        $user = $this->employee($department);

        $rule = AssignmentRule::query()->create([
            'course_id' => $course->id,
            'target_type' => AssignmentRule::TARGET_DEPARTMENT,
            'target_id' => $department->id,
            'due_days' => 7,
            'is_active' => true,
        ]);

        app(SyncAssignmentRules::class)->forRule($rule);

        $enrollment = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->assertEqualsWithDelta(
            now()->addDays(7)->timestamp,
            $enrollment->due_at->timestamp,

            // Generous: the due date is set when the enrollment is created and
            // compared when the assertion runs, and a full suite takes minutes.
            // The point is "the right number of days out", not the second.
            120,
        );
    }
}
