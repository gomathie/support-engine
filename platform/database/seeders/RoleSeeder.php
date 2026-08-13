<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Three roles, as the brief requires, but backed by named permissions so a
     * fourth (say, a trainer who authors content but manages nobody) is a
     * configuration change rather than a code change.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Content authoring
            'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish',
            'lessons.manage',
            'quizzes.manage',

            // People
            'employees.view', 'employees.create', 'employees.update', 'employees.deactivate',
            'departments.manage',
            'roles.assign',

            // Assignment
            'enrollments.view', 'enrollments.create', 'enrollments.delete',
            'assignment-rules.manage',

            // Reporting
            'reports.view', 'reports.view-all-departments', 'reports.export',

            // Certificates
            'certificates.view', 'certificates.revoke',

            // Support panel content
            'diagnostics.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate(RoleEnum::Admin->value, 'web');
        $admin->syncPermissions($permissions);

        // A manager runs people, not the curriculum. They can see their own
        // departments' data and assign training, but not author or publish it,
        // and explicitly not reports across the whole company.
        $manager = Role::findOrCreate(RoleEnum::Manager->value, 'web');
        $manager->syncPermissions([
            'courses.view',
            'employees.view',
            'enrollments.view', 'enrollments.create', 'enrollments.delete',
            'reports.view',
            'certificates.view',
        ]);

        // Employees hold no admin permissions at all. Everything they can do is
        // decided by the policies on the employee-facing routes.
        Role::findOrCreate(RoleEnum::Employee->value, 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
