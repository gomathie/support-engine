<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: roles before users (users are given roles), departments
     * before users (users belong to one), content before assignment rules
     * (rules point at courses).
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            TrainingContentSeeder::class,
            PilotExamSeeder::class,
            DiagnosticTreeSeeder::class,
            AssignmentRuleSeeder::class,
        ]);
    }
}
