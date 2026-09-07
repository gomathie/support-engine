<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the calendar framing, and point each unit at the documentation.
 *
 * "Day 1 … Day 14" and "2 week plan" describe how long the training was
 * scheduled to take, which is the wrong thing to put in front of a trainee for
 * two reasons: somebody who needs three days for Day 2 is not behind, and
 * somebody who finishes on time has not therefore learned anything. Progress is
 * measured by assessment now, so the units are numbered rather than dated.
 *
 * `Day 11–12` and `Day 13–14` collapse into single numbered units, so the
 * sequence stays 1..12 with no gaps.
 *
 * `docs_reference` names the chapter of docs.pilot-gps.com each unit is drawn
 * from. Stored as a reference rather than a URL: the documentation is versioned
 * (7.10 at the time of writing) and deep links would rot at the next release,
 * whereas the chapter names are stable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->string('docs_reference')->nullable()->after('description');
        });

        $this->renameCourses([
            '1st-line support — 2 week plan' => '1st-line support',
            'Admin panel — 3 day plan' => 'Admin panel',
        ]);

        // subtitle => [new title, documentation chapter]
        $this->retitleModules([
            'Introduction to PILOT and basic concepts' => ['Lesson 1', 'About the platform · Before you start → Glossary'],
            'Interface and navigation' => ['Lesson 2', 'User account → Interface overview, Top panel, Workspace, Map'],
            'User and rights management' => ['Lesson 3', 'Before you start → Roles and access rights · User account → Staff and groups'],
            'Working with objects (part 1)' => ['Lesson 4', 'Objects → Adding an object, Object card, Main object settings'],
            'Working with objects (part 2) and object list' => ['Lesson 5', 'Objects → Object list, Object menu · User account → Object tags'],
            'Sensors (part 1)' => ['Lesson 6', 'Sensors → Adding sensors, Sensor types'],
            'Sensors (part 2)' => ['Lesson 7', 'Sensors → Calibration tables, Formulas and handlers, Sensor templates'],
            'History and reports' => ['Lesson 8', 'History → Viewing history · Reports → Building reports, Report types'],
            'Contract settings and notifications' => ['Lesson 9', 'Modules → Notifications · User account → Account settings'],
            'Additional modules and tools' => ['Lesson 10', 'Modules · Report builder'],
            'Comprehensive review and call simulation' => ['Lesson 11', 'FAQ · Additional Admin Features → Troubleshooting'],
            'Final testing and consultation' => ['Lesson 12', 'FAQ'],

            'Interface familiarization and basic operations' => ['Lesson 1', 'Admin Panel → Panel overview, Account management, Users'],
            'Objects, partners, and finances' => ['Lesson 2', 'Admin Panel → Vehicles, Configuration · Finance → Partners, Email configuration'],
            'Modules, notifications, security, rebranding' => ['Lesson 3', 'Admin Panel → Modules · Administrator Panel → Security, Rebranding, Permissions'],
            'Combined assessment' => ['Final assessment', 'Admin Panel · Finance'],
        ]);
    }

    public function down(): void
    {
        $this->renameCourses([
            '1st-line support' => '1st-line support — 2 week plan',
            'Admin panel' => 'Admin panel — 3 day plan',
        ]);

        $this->retitleModules([
            'Introduction to PILOT and basic concepts' => ['Day 1', null],
            'Interface and navigation' => ['Day 2', null],
            'User and rights management' => ['Day 3', null],
            'Working with objects (part 1)' => ['Day 4', null],
            'Working with objects (part 2) and object list' => ['Day 5', null],
            'Sensors (part 1)' => ['Day 6', null],
            'Sensors (part 2)' => ['Day 7', null],
            'History and reports' => ['Day 8', null],
            'Contract settings and notifications' => ['Day 9', null],
            'Additional modules and tools' => ['Day 10', null],
            'Comprehensive review and call simulation' => ['Day 11–12', null],
            'Final testing and consultation' => ['Day 13–14', null],
            'Interface familiarization and basic operations' => ['Day 1', null],
            'Objects, partners, and finances' => ['Day 2', null],
            'Modules, notifications, security, rebranding' => ['Day 3', null],
            'Combined assessment' => ['Final test', null],
        ]);

        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropColumn('docs_reference');
        });
    }

    /** @param  array<string, string>  $map */
    private function renameCourses(array $map): void
    {
        foreach ($map as $from => $to) {
            DB::table('courses')
                ->where('title', $from)
                ->update(['title' => $to, 'updated_at' => now()]);
        }
    }

    /**
     * Matched on subtitle, which is unique per course and unchanged by this
     * migration — matching on "Day 1" would hit two different courses.
     *
     * @param  array<string, array{0: string, 1: ?string}>  $map
     */
    private function retitleModules(array $map): void
    {
        foreach ($map as $subtitle => [$title, $docs]) {
            DB::table('course_modules')
                ->where('subtitle', $subtitle)
                ->update([
                    'title' => $title,
                    'docs_reference' => $docs,
                    'updated_at' => now(),
                ]);
        }
    }
};
