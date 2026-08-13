<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Roles are referenced by name throughout the policies, so every test
        // needs them to exist. Cheaper than running the full RoleSeeder.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::cases() as $role) {
            SpatieRole::findOrCreate($role->value, 'web');
        }
    }

    protected function employee(?Department $department = null, array $attributes = []): User
    {
        $user = User::factory()->create([
            'department_id' => $department?->id,
            'is_active' => true,
            ...$attributes,
        ]);

        $user->assignRole(Role::Employee->value);

        return $user;
    }

    protected function manager(?Department $department = null, array $attributes = []): User
    {
        $user = User::factory()->create([
            'department_id' => $department?->id,
            'is_active' => true,
            ...$attributes,
        ]);

        $user->assignRole(Role::Manager->value);

        if ($department) {
            $user->managedDepartments()->attach($department);
        }

        return $user;
    }

    protected function admin(array $attributes = []): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            ...$attributes,
        ]);

        $user->assignRole(Role::Admin->value);

        return $user;
    }
}
