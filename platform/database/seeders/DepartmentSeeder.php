<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Technical Support', 'description' => '1st and 2nd line support desk.'],
            ['name' => 'Operations', 'description' => 'Fleet operations and dispatch.'],
            ['name' => 'Installations', 'description' => 'Field engineers and device installation.'],
            ['name' => 'Integrations', 'description' => 'Relay endpoints and external systems.'],
            ['name' => 'Sales', 'description' => 'Commercial, tariffs and module activation.'],
        ];

        foreach ($departments as $department) {
            Department::query()->firstOrCreate(
                ['name' => $department['name']],
                $department,
            );
        }
    }
}
