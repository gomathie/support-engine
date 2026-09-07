<?php

namespace Database\Seeders;

use App\Actions\Cohorts\AssignTrainee;
use App\Actions\Practical\SubmitPracticalTask;
use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\PracticalTask;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample data for the Phase 2 features, so they can be clicked through rather
 * than read about.
 *
 * Deliberately NOT in DatabaseSeeder: this is demonstration content, not part of
 * the curriculum, and a fresh environment should not silently acquire a fake
 * practical task. Run it by hand:
 *
 *     php artisan db:seed --class=DemoCompetencySeeder
 *
 * Idempotent — running it twice changes nothing.
 */
class DemoCompetencySeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::query()->where('title', 'like', '1st-line support%')->first()
            ?? Course::query()->first();

        if (! $course) {
            $this->command?->warn('No courses found — run the main seeders first.');

            return;
        }

        $this->seedVideoLesson($course);
        $this->seedPracticalTask($course);
        $this->seedCohort();

        $this->command?->info('Demo data ready. See the course, the admin panel and Success metrics.');
    }

    /** A video lesson, so the embedded player and transcript are visible. */
    private function seedVideoLesson(Course $course): void
    {
        $module = $course->modules()->orderBy('position')->first()
            ?? CourseModule::factory()->for($course)->create(['title' => 'Demo module']);

        Lesson::query()->updateOrCreate(
            ['course_module_id' => $module->getKey(), 'slug' => 'demo-video-sensor-basics'],
            [
                'title' => 'Reading a fuel sensor (demo video)',
                'description' => 'A short walkthrough of the sensor tab and what the raw value tells you.',
                'type' => LessonType::VideoEmbed,
                'video_provider' => 'youtube',
                'video_id' => 'aqz-KE-bpKQ',
                'video_duration_seconds' => 372,
                'video_transcript' => "Open the object card and go to the sensors tab.\n\n"
                    ."Check the raw value first. If the raw value is arriving and looks sane, "
                    ."the problem is configuration rather than hardware — do not send an engineer yet.\n\n"
                    ."Then check the field mapping, and only then the conversion formula. "
                    ."That order matters: the layer model tells you to rule out the cheapest cause first.\n\n"
                    ."Finally, verify. Take a second reading and confirm it moves the way you expect.",
                'completion_requirement' => CompletionRequirement::View,
                'estimated_minutes' => 7,
                'is_published' => true,
            ],
        );
    }

    /** A practical task with one submission already waiting to be marked. */
    private function seedPracticalTask(Course $course): void
    {
        $task = PracticalTask::query()->updateOrCreate(
            ['course_id' => $course->getKey(), 'slug' => 'configure-a-fuel-sensor'],
            [
                'title' => 'Configure a fuel sensor from raw data',
                'brief' => '<p>A customer reports that fuel readings on one vehicle dropped to zero overnight. '
                    .'Sensors tracing shows the raw value arriving normally and the satellite count is 9.</p>'
                    .'<p>Diagnose it, fix the configuration, and verify the fix.</p>',
                'submission_instructions' => 'Write up what you checked and in what order, then attach a screenshot of the sensor tab after your fix.',
                'expected_evidence' => 'A screenshot showing the corrected mapping, and a second reading confirming the value moves as expected.',
                'estimated_minutes' => 45,
                'is_published' => true,

                // On by default here so the split-verdict behaviour can be seen.
                'requires_second_marker' => true,
            ],
        );

        $trainee = User::query()->where('email', 'employee@pilot.test')->first();

        if (! $trainee || $task->submissions()->where('user_id', $trainee->getKey())->exists()) {
            return;
        }

        $draft = app(SubmitPracticalTask::class)->draftFor($trainee, $task);

        app(SubmitPracticalTask::class)->submit(
            $draft,
            "I checked the raw value first — it was arriving normally at around 4000, so I ruled out the probe.\n\n"
            ."Then I looked at the field mapping and found it pointing at the wrong parameter after the firmware update. "
            ."I corrected it and re-ran the calculation.\n\n"
            ."To verify I took a second reading twenty minutes later and confirmed the level moved in the right direction.",
        );
    }

    /** A cohort link, so the marking queue and assignment history have something in them. */
    private function seedCohort(): void
    {
        $trainer = User::query()->where('email', 'manager@pilot.test')->first();
        $trainee = User::query()->where('email', 'employee@pilot.test')->first();
        $admin = User::query()->where('email', 'admin@pilot.test')->first();

        if (! $trainer || ! $trainee || ! $admin) {
            return;
        }

        app(AssignTrainee::class)->handle($trainee, $trainer, $admin, 'Initial cohort assignment.');
    }
}
