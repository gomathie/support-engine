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
            // Section A of the examination, with its answer key.
            PilotExamSeeder::class,

            // Sections B and C. Scoped to the last module rather than seeded as
            // final exams, because a course may only have one of those.
            WrittenExamSeeder::class,
            DiagnosticTreeSeeder::class,
            AssignmentRuleSeeder::class,
        ]);
    }
}
