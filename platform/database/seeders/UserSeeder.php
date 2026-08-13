<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Local development accounts. All three share the same obvious password
     * because this only ever runs against a development database — production
     * accounts are created through the admin panel.
     */
    public function run(): void
    {
        $support = Department::query()->where('name', 'Technical Support')->first();
        $operations = Department::query()->where('name', 'Operations')->first();

        $admin = $this->make('Ada Okonkwo', 'admin@pilot.test', $support, [
            'job_title' => 'Head of Technical Support',
            'employee_number' => 'PT-0001',
        ]);
        $admin->syncRoles([Role::Admin->value]);

        $manager = $this->make('Marcus Bell', 'manager@pilot.test', $support, [
            'job_title' => 'Support Team Lead',
            'employee_number' => 'PT-0002',
        ]);
        $manager->syncRoles([Role::Manager->value]);

        // The manager runs the support desk; this is what scopes every report
        // and every employee record they are allowed to see.
        $manager->managedDepartments()->syncWithoutDetaching([$support->id]);

        $employee = $this->make('Nadia Prasad', 'employee@pilot.test', $support, [
            'job_title' => '1st Line Support Engineer',
            'employee_number' => 'PT-0003',
        ]);
        $employee->syncRoles([Role::Employee->value]);

        // A second employee outside the manager's departments, so authorization
        // tests have somebody the manager must *not* be able to see.
        $outsider = $this->make('Tom Lindqvist', 'operations@pilot.test', $operations, [
            'job_title' => 'Fleet Coordinator',
            'employee_number' => 'PT-0004',
        ]);
        $outsider->syncRoles([Role::Employee->value]);
    }

    private function make(string $name, string $email, ?Department $department, array $extra = []): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'department_id' => $department?->id,
                'email_verified_at' => now(),
                'is_active' => true,
                ...$extra,
            ],
        );
    }
}
