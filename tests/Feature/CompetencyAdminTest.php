<?php

namespace Tests\Feature;

use App\Filament\Resources\CompetencyAreas\CompetencyAreaResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Levels\LevelResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\CompetencyArea;
use App\Models\Course;
use App\Models\Level;
use App\Models\LevelRequirement;
use App\Models\TraineeLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin surface for the ladder (PA-8).
 *
 * The boundary that matters here is authoring: a Trainer may see the ladder but
 * not edit it, because somebody who can change the requirements can lower the
 * bar for their own cohort.
 */
class CompetencyAdminTest extends TestCase
{
    use RefreshDatabase;

    private function ladder(): array
    {
        $level = Level::factory()->at(1)->create(['name' => 'Basic']);
        $area = CompetencyArea::factory()->create(['name' => 'Objects & Sensors']);
        $course = Course::factory()->create();

        LevelRequirement::query()->create([
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
            'course_id' => $course->id,
        ]);

        return compact('level', 'area', 'course');
    }

    public function test_the_competency_screens_render_for_an_admin(): void
    {
        ['level' => $level, 'area' => $area] = $this->ladder();

        $this->actingAs($this->admin());

        $pages = [
            LevelResource::getUrl('index'),
            LevelResource::getUrl('create'),
            LevelResource::getUrl('edit', ['record' => $level]),
            CompetencyAreaResource::getUrl('index'),
            CompetencyAreaResource::getUrl('create'),
            CompetencyAreaResource::getUrl('edit', ['record' => $area]),
        ];

        foreach ($pages as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_a_trainer_may_read_the_ladder_but_not_author_it(): void
    {
        $this->ladder();

        $trainer = $this->trainer();

        $this->actingAs($trainer)
            ->get(LevelResource::getUrl('index'))
            ->assertSuccessful();

        $this->assertTrue($trainer->can('competency.view'));
        $this->assertFalse($trainer->can('competency.manage'));

        $this->assertFalse(LevelResource::canCreate());
        $this->assertFalse(CompetencyAreaResource::canCreate());
    }

    public function test_an_admin_may_author_the_ladder(): void
    {
        $this->actingAs($this->admin());

        $this->assertTrue(LevelResource::canCreate());
        $this->assertTrue(CompetencyAreaResource::canCreate());
    }

    /**
     * The course form is the everyday way a course is put on the ladder, so the
     * requirement row the award engine reads has to follow it. Otherwise a
     * retagged course keeps awarding the old level from a stale row.
     */
    public function test_tagging_a_course_creates_the_matching_requirement(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();
        $course = Course::factory()->create();

        $course->update([
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
        ]);

        $this->assertDatabaseHas('level_requirements', [
            'course_id' => $course->id,
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
        ]);
    }

    public function test_retagging_a_course_removes_the_stale_requirement(): void
    {
        $level = Level::factory()->at(1)->create();
        $first = CompetencyArea::factory()->create();
        $second = CompetencyArea::factory()->create();

        $course = Course::factory()->create([
            'level_id' => $level->id,
            'competency_area_id' => $first->id,
        ]);

        $course->update(['competency_area_id' => $second->id]);

        $this->assertDatabaseMissing('level_requirements', [
            'course_id' => $course->id,
            'competency_area_id' => $first->id,
        ]);

        $this->assertDatabaseHas('level_requirements', [
            'course_id' => $course->id,
            'competency_area_id' => $second->id,
        ]);
    }

    /**
     * A course may count towards more than one pair. Those extra requirements
     * are authored on the level, and retagging the course must not delete them.
     */
    public function test_retagging_leaves_requirements_for_other_pairs_alone(): void
    {
        $basic = Level::factory()->at(1)->create();
        $second = Level::factory()->at(2)->create();
        $area = CompetencyArea::factory()->create();
        $otherArea = CompetencyArea::factory()->create();

        $course = Course::factory()->create([
            'level_id' => $basic->id,
            'competency_area_id' => $area->id,
        ]);

        // Authored separately, on the level rather than the course.
        LevelRequirement::query()->create([
            'level_id' => $second->id,
            'competency_area_id' => $otherArea->id,
            'course_id' => $course->id,
        ]);

        $course->update(['level_id' => $second->id, 'competency_area_id' => $otherArea->id]);

        $this->assertSame(1, LevelRequirement::query()->where('course_id', $course->id)->count());

        $this->assertDatabaseHas('level_requirements', [
            'course_id' => $course->id,
            'level_id' => $second->id,
            'competency_area_id' => $otherArea->id,
        ]);
    }

    public function test_the_course_form_offers_the_ladder(): void
    {
        $this->ladder();

        $course = Course::factory()->create();

        $this->actingAs($this->admin())
            ->get(CourseResource::getUrl('edit', ['record' => $course]))
            ->assertSuccessful()
            ->assertSee('Competency area');
    }

    /** The users list is where "who is Level 1 in Sensors" gets asked. */
    public function test_the_user_list_shows_awarded_levels(): void
    {
        ['level' => $level, 'area' => $area] = $this->ladder();

        $trainee = $this->trainee();

        TraineeLevel::query()->create([
            'user_id' => $trainee->id,
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
            'awarded_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Basic');
    }
}
