<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Three roles, backed by named permissions.
 *
 * The roles describe what someone does on the training portal, not where they
 * sit on an org chart — a Trainer is not necessarily anybody's line manager.
 *
 * Every Trainer capability is a separate permission rather than an implied tier,
 * so "can build quizzes" can be granted without also granting "can assign
 * trainees". That separation is what prevents the accidental role escalation in
 * §6(f) of the implementation plan.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate(RoleEnum::Admin->value, 'web');
        $admin->syncPermissions($this->permissions());

        /*
         * A Trainer builds content and grades their own cohort. Two boundaries
         * matter here:
         *
         *  - transcripts.view-all lets them READ every trainee's record, but
         *    grading stays restricted to their assigned cohort. Those used to
         *    be one department-shaped rule; they are now separate.
         *  - trainees.assign is deliberately absent. Only an Admin decides who
         *    trains whom, otherwise a Trainer could assign themselves work or
         *    quietly hand a struggling trainee to somebody else.
         */
        $trainer = Role::findOrCreate(RoleEnum::Trainer->value, 'web');
        $trainer->syncPermissions([
            /*
             * A trainer authors the whole course, not fragments of one.
             *
             * They previously held lessons.manage and quizzes.manage but not
             * courses.create — able to write the lessons and the exam, but not
             * the course those sit in, which meant every new course needed an
             * administrator. Authoring is the job; the course is the unit of it.
             *
             * courses.delete is deliberately absent. Deleting a course takes
             * other people's training records, certificates and level awards
             * with it, and that is an administrator's decision.
             */
            'courses.view', 'courses.create', 'courses.update', 'courses.publish',
            'lessons.manage',
            'quizzes.manage',
            'content.audit',
            'videos.manage',
            'employees.view',
            'transcripts.view-all',
            'enrollments.view', 'enrollments.create', 'enrollments.delete',
            'grades.override',
            'competency.view',
            'reports.view',
            'certificates.view',
        ]);

        // Trainees hold no admin permissions at all. Everything they can do is
        // decided by the policies on the employee-facing routes.
        Role::findOrCreate(RoleEnum::Trainee->value, 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return array<int, string> */
    private function permissions(): array
    {
        return [
            // Content authoring
            'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish',
            'lessons.manage',
            'quizzes.manage',
            'videos.manage',
            'content.audit',

            // People
            'employees.view', 'employees.create', 'employees.update', 'employees.deactivate',
            'departments.manage',
            'roles.assign',

            // Cohorts — who trains whom
            'trainees.assign',
            'trainees.reassign',
            'transcripts.view-all',

            // Assignment of training
            'enrollments.view', 'enrollments.create', 'enrollments.delete',
            'assignment-rules.manage',

            // Assessment
            'grades.override',

            // The competency ladder itself — rungs, areas and what each demands.
            // Admin only: a Trainer who could edit the requirements could lower
            // the bar for their own cohort.
            'competency.manage',
            'competency.view',

            // Reporting
            'reports.view', 'reports.view-all-departments', 'reports.export',

            // Certificates
            'certificates.view', 'certificates.revoke',

            // Support panel content
            'diagnostics.manage',
        ];
    }
}
