<?php

namespace Database\Seeders;

use App\Models\CompetencyArea;
use App\Models\Course;
use App\Models\Level;
use App\Models\LevelRequirement;
use Illuminate\Database\Seeder;

/**
 * The competency ladder and the areas it is held in.
 *
 * The levels and areas here are settled — they come from the training plan. The
 * course-to-requirement mapping at the bottom is deliberately partial: only the
 * four seeded courses exist today, and which rung each one earns is a question
 * for the Phase 1 content audit (PA-5/PA-6), not something to invent here.
 *
 * Areas with no requirements cannot be awarded at all, which is the correct
 * behaviour for a rung nobody has defined yet.
 */
class CompetencySeeder extends Seeder
{
    public function run(): void
    {
        $levels = $this->seedLevels();
        $areas = $this->seedAreas();

        $this->seedRequirements($levels, $areas);
    }

    /** @return array<string, Level> keyed by slug */
    private function seedLevels(): array
    {
        $levels = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'position' => 1,
                'description' => 'Handles routine 1st-line work unaided: reads the data, follows the procedure, knows when to escalate.',
            ],
            [
                'name' => 'Second',
                'slug' => 'second',
                'position' => 2,
                'description' => 'Diagnoses non-obvious faults, configures rather than only reads, and answers other people\'s questions.',
            ],
            [
                'name' => 'Third',
                'slug' => 'third',
                'position' => 3,
                'description' => 'Specialist depth — server-side work, integrations, and the cases nobody below could close.',
            ],
        ];

        $result = [];

        foreach ($levels as $level) {
            $result[$level['slug']] = Level::query()->updateOrCreate(
                ['slug' => $level['slug']],
                $level,
            );
        }

        return $result;
    }

    /** @return array<string, CompetencyArea> keyed by slug */
    private function seedAreas(): array
    {
        $areas = [
            [
                'name' => 'Access & Rights',
                'slug' => 'access-rights',
                'position' => 1,
                'description' => 'Partners, contracts, users and what each of them is permitted to see.',
            ],
            [
                'name' => 'Objects & Sensors',
                'slug' => 'objects-sensors',
                'position' => 2,
                'description' => 'Object creation, device configuration, sensor calibration and mileage sources.',
            ],
            [
                'name' => 'Reporting',
                'slug' => 'reporting',
                'position' => 3,
                'description' => 'Standard reports, the report builder, and reconciling points against graphs.',
            ],
            [
                'name' => 'Notifications',
                'slug' => 'notifications',
                'position' => 4,
                'description' => 'Triggers, multi-condition rules, geofences and route control.',
            ],
            [
                'name' => 'Escalation',
                'slug' => 'escalation',
                'position' => 5,
                'description' => 'Judging severity, writing a handover a 2nd-line engineer can act on, and customer communication under pressure.',
            ],
            [
                'name' => 'Admin Panel',
                'slug' => 'admin-panel',
                'position' => 6,
                'description' => 'Server-side administration: database operations, recalculations, logs and retransmission.',
            ],
        ];

        $result = [];

        foreach ($areas as $area) {
            $result[$area['slug']] = CompetencyArea::query()->updateOrCreate(
                ['slug' => $area['slug']],
                $area,
            );
        }

        return $result;
    }

    /**
     * Provisional mapping of the four existing courses onto the ladder.
     *
     * @param  array<string, Level>  $levels
     * @param  array<string, CompetencyArea>  $areas
     */
    private function seedRequirements(array $levels, array $areas): void
    {
        $mapping = [
            // course title => [level slug, area slug]
            'Onboarding: IT Support Tools & Policies' => ['basic', 'access-rights'],
            '1st-line support — 2 week plan' => ['basic', 'objects-sensors'],
            'Support skills — communication, troubleshooting, escalation, standards' => ['basic', 'escalation'],
            'Admin panel — 3 day plan' => ['second', 'admin-panel'],
        ];

        foreach ($mapping as $title => [$levelSlug, $areaSlug]) {
            $course = Course::query()->where('title', $title)->first();

            // The content seeders may not have run, or a title may have been
            // edited in the admin panel. Neither is a reason to fail the seed.
            if (! $course) {
                continue;
            }

            $level = $levels[$levelSlug];
            $area = $areas[$areaSlug];

            $course->forceFill([
                'level_id' => $level->getKey(),
                'competency_area_id' => $area->getKey(),
            ])->save();

            LevelRequirement::query()->firstOrCreate([
                'level_id' => $level->getKey(),
                'competency_area_id' => $area->getKey(),
                'course_id' => $course->getKey(),
            ]);
        }
    }
}
