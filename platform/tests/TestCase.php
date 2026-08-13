<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->guardAgainstRunningOnTheWrongDatabase();

        // Roles and permissions are referenced by name throughout the policies,
        // so every test needs them to exist. Runs the real seeder rather than a
        // stripped-down copy, so a permission added there cannot drift out of
        // sync with what the policies ask for.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RoleSeeder::class);

        foreach (Role::cases() as $role) {
            SpatieRole::findOrCreate($role->value, 'web');
        }
    }

    /**
     * A cached config, a stray .env or a wrong compose variable can quietly
     * point the suite at the development database. RefreshDatabase wraps each
     * test in a transaction so the damage is usually invisible — right up until
     * something calls migrate:fresh and the dev data is gone.
     *
     * Fail loudly instead.
     */
    private function guardAgainstRunningOnTheWrongDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $looksLikeTesting = $database === ':memory:'
            || str_contains((string) $database, 'test');

        if ($looksLikeTesting) {
            return;
        }

        $this->fail(
            "Refusing to run tests against the '{$database}' database.\n"
            ."Expected a database whose name contains 'test'.\n\n"
            ."The usual cause is a cached config: `php artisan config:clear`.\n"
            .'app.env is currently '.config('app.env').'.'
        );
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
