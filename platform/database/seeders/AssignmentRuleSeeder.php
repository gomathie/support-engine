<?php

namespace Database\Seeders;

use App\Actions\Enrollment\SyncAssignmentRules;
use App\Enums\Role;
use App\Models\AssignmentRule;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demonstrates the assignment architecture the brief asks for:
 *
 *   Department: Technical Support -> automatically assign the 1st-line plan
 *
 * The mapping is a row, not a branch in code, so an administrator can add
 * another without a deploy.
 */
class AssignmentRuleSeeder extends Seeder
{
    public function run(SyncAssignmentRules $sync): void
    {
        $support = Department::query()->where('name', 'Technical Support')->first();
        $admin = User::query()->role(Role::Admin->value)->first();

        $rules = [
            ['category' => 'TRACK 1', 'target' => $support, 'due_days' => 21],
            ['category' => 'TRACK 3', 'target' => $support, 'due_days' => 30],
            ['category' => 'PREP', 'target' => $support, 'due_days' => null],
        ];

        foreach ($rules as $rule) {
            $course = Course::query()->where('category', $rule['category'])->first();

            if (! $course || ! $rule['target']) {
                continue;
            }

            AssignmentRule::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'target_type' => AssignmentRule::TARGET_DEPARTMENT,
                    'target_id' => $rule['target']->id,
                ],
                [
                    'due_days' => $rule['due_days'],
                    'is_active' => true,
                    'applies_retroactively' => true,
                    'created_by' => $admin?->id,
                ],
            );
        }

        // Turn the rules into actual enrollments for everybody they match.
        $sync->all();
    }
}
