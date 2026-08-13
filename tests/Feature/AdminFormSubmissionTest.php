<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

/**
 * Submitting admin forms, not just rendering them.
 *
 * AdminPanelTest opens every screen and asserts a 200, which is worth having
 * but cannot catch a form that renders perfectly and explodes on save. A roles
 * select that offered role *names* while the pivot stores *ids* did exactly
 * that: the page looked right and saving died in Postgres with
 * "invalid input syntax for type bigint".
 */
class AdminFormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function roleId(Role $role): int
    {
        return SpatieRole::query()->where('name', $role->value)->value('id');
    }

    public function test_an_admin_can_create_an_employee_with_a_role(): void
    {
        $department = Department::factory()->create();

        $this->actingAs($this->admin());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Igor Che',
                'email' => 'igor@pilot.test',
                'employee_number' => 'PT-101',
                'job_title' => 'Support Engineer',
                'department_id' => $department->id,
                'password' => 'a-strong-password',
                'is_active' => true,
                'roles' => [$this->roleId(Role::Manager)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', 'igor@pilot.test')->firstOrFail();

        $this->assertTrue($created->hasRole(Role::Manager->value));
        $this->assertSame($department->id, $created->department_id);
        $this->assertTrue($created->is_active);

        // Stored hashed, never in the clear.
        $this->assertNotSame('a-strong-password', $created->password);
        $this->assertTrue(password_verify('a-strong-password', $created->password));
    }

    public function test_an_admin_can_change_an_employees_role(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $employee->getKey()])
            ->fillForm([
                'roles' => [$this->roleId(Role::Manager)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $employee->refresh();

        $this->assertTrue($employee->hasRole(Role::Manager->value));
        $this->assertFalse($employee->hasRole(Role::Employee->value));
    }

    /** Blank means "leave it alone", not "set the password to empty". */
    public function test_saving_an_employee_without_a_password_keeps_the_old_one(): void
    {
        $employee = $this->employee();
        $original = $employee->password;

        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $employee->getKey()])
            ->fillForm([
                'name' => 'Renamed Person',
                'password' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $employee->refresh();

        $this->assertSame('Renamed Person', $employee->name);
        $this->assertSame($original, $employee->password);
    }

    public function test_a_duplicate_email_is_rejected_rather_than_saved(): void
    {
        $existing = $this->employee();

        $this->actingAs($this->admin());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Somebody Else',
                'email' => $existing->email,
                'password' => 'a-strong-password',
                'roles' => [$this->roleId(Role::Employee)],
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_an_admin_can_save_a_course(): void
    {
        $course = Course::factory()->create(['title' => 'Before']);

        $this->actingAs($this->admin());

        // getRouteKey(), not getKey(): Course binds by slug, and Filament
        // resolves the record through the route key like the real links do.
        Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->fillForm([
                'title' => 'After',
                'summary' => 'An edited summary.',
                'difficulty' => 'intermediate',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $course->refresh();

        $this->assertSame('After', $course->title);
        $this->assertSame('An edited summary.', $course->summary);
    }
}
