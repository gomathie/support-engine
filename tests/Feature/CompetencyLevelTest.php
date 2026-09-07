<?php

namespace Tests\Feature;

use App\Actions\Competency\AwardCompetencyLevel;
use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteTopic;
use App\Models\CompetencyArea;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\Level;
use App\Models\LevelRequirement;
use App\Models\TraineeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * The competency ladder (PA-8).
 *
 * The question the whole feature exists to answer is "who is Level 1 in
 * Sensors", so the tests are written around that: an award is per rung *and*
 * per area, earned by finishing a defined set of courses, and it does not
 * appear until the last of them is done.
 */
class CompetencyLevelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Certificate rendering is queued on completion; irrelevant here.
        Bus::fake();
    }

    private function completableCourse(int $topics = 2): Course
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        Topic::factory()->count($topics)->for($lesson, 'lesson')->create();

        return $course->fresh();
    }

    private function complete(User $user, Course $course): void
    {
        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->topics as $topic) {
            app(CompleteTopic::class)->handle($user, $topic);
        }
    }

    private function requireCourses(Level $level, CompetencyArea $area, Course ...$courses): void
    {
        foreach ($courses as $course) {
            LevelRequirement::query()->create([
                'level_id' => $level->getKey(),
                'competency_area_id' => $area->getKey(),
                'course_id' => $course->getKey(),
            ]);
        }
    }

    public function test_finishing_the_required_course_awards_the_level(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();
        $course = $this->completableCourse();

        $this->requireCourses($level, $area, $course);

        $user = $this->trainee();
        $this->complete($user, $course);

        $this->assertTrue($user->holdsLevel($level, $area));
        $this->assertSame($level->id, $user->levelIn($area)?->id);
    }

    /**
     * The point of requirements being a table rather than a column: a rung is
     * earned by a *set* of courses, so finishing one of two earns nothing.
     */
    public function test_a_level_is_not_awarded_until_every_required_course_is_complete(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();
        $first = $this->completableCourse();
        $second = $this->completableCourse();

        $this->requireCourses($level, $area, $first, $second);

        $user = $this->trainee();

        $this->complete($user, $first);
        $this->assertFalse($user->holdsLevel($level, $area));

        $this->complete($user, $second);
        $this->assertTrue($user->holdsLevel($level, $area));
    }

    /** Awarding runs on every topic tick, so it has to be safe to repeat. */
    public function test_awards_are_not_duplicated(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();
        $course = $this->completableCourse();

        $this->requireCourses($level, $area, $course);

        $user = $this->trainee();
        $this->complete($user, $course);

        app(AwardCompetencyLevel::class)->forCourse($user, $course);
        app(AwardCompetencyLevel::class)->forCourse($user, $course);

        $this->assertSame(1, TraineeLevel::query()->where('user_id', $user->id)->count());
    }

    /**
     * A level is held per area. Competence at reporting says nothing about
     * competence with devices, and the model must not blur the two.
     */
    public function test_a_level_is_held_per_area_not_globally(): void
    {
        $level = Level::factory()->at(1)->create();
        $reporting = CompetencyArea::factory()->create(['name' => 'Reporting']);
        $sensors = CompetencyArea::factory()->create(['name' => 'Sensors']);

        $reportingCourse = $this->completableCourse();
        $sensorsCourse = $this->completableCourse();

        $this->requireCourses($level, $reporting, $reportingCourse);
        $this->requireCourses($level, $sensors, $sensorsCourse);

        $user = $this->trainee();
        $this->complete($user, $reportingCourse);

        $this->assertTrue($user->holdsLevel($level, $reporting));
        $this->assertFalse($user->holdsLevel($level, $sensors));
    }

    /**
     * The ladder is ordered. A mis-assigned advanced course must not vault a
     * trainee past the foundation of the same area.
     */
    public function test_a_higher_rung_is_withheld_until_the_one_below_is_held(): void
    {
        $basic = Level::factory()->at(1)->create(['name' => 'Basic']);
        $second = Level::factory()->at(2)->create(['name' => 'Second']);
        $area = CompetencyArea::factory()->create();

        $basicCourse = $this->completableCourse();
        $secondCourse = $this->completableCourse();

        $this->requireCourses($basic, $area, $basicCourse);
        $this->requireCourses($second, $area, $secondCourse);

        $user = $this->trainee();

        // Out of order: the advanced course first.
        $this->complete($user, $secondCourse);
        $this->assertFalse($user->holdsLevel($second, $area));

        // Finishing the foundation releases the rung above on its next check.
        $this->complete($user, $basicCourse);
        app(AwardCompetencyLevel::class)->forCourse($user, $secondCourse);

        $this->assertTrue($user->holdsLevel($basic, $area));
        $this->assertTrue($user->holdsLevel($second, $area));
        $this->assertSame($second->id, $user->levelIn($area)?->id);
    }

    /**
     * Not every area starts at Basic. Where the rung below is not configured
     * for that area, there is nothing to wait for.
     */
    public function test_an_unconfigured_lower_rung_does_not_block_the_award(): void
    {
        Level::factory()->at(1)->create(['name' => 'Basic']);
        $second = Level::factory()->at(2)->create(['name' => 'Second']);

        $area = CompetencyArea::factory()->create();
        $course = $this->completableCourse();

        $this->requireCourses($second, $area, $course);

        $user = $this->trainee();
        $this->complete($user, $course);

        $this->assertTrue($user->holdsLevel($second, $area));
    }

    /**
     * A pair nobody has defined requirements for is unconfigured, not earned.
     * Otherwise the Phase 1 audit backlog would read as everybody being fully
     * competent in every area.
     */
    public function test_a_pair_with_no_requirements_is_never_awarded(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();

        $user = $this->trainee();

        $this->assertNull(app(AwardCompetencyLevel::class)->evaluate($user, $level->id, $area->id));
        $this->assertFalse($user->holdsLevel($level, $area));
    }

    /** The award records which sitting earned it. */
    public function test_the_award_records_the_evidence_and_that_the_system_granted_it(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();
        $course = $this->completableCourse();

        $this->requireCourses($level, $area, $course);

        $user = $this->trainee();
        $this->complete($user, $course);

        $award = TraineeLevel::query()->where('user_id', $user->id)->sole();

        $this->assertNull($award->awarded_by, 'A system award has no granting user.');
        $this->assertNotNull($award->awarded_at);
        $this->assertTrue($award->isActive());
    }

    /**
     * Withdrawn, not deleted — "she held Level 1 until March" stays true, and a
     * revoked award is not silently re-granted by the next topic tick.
     */
    public function test_a_revoked_award_is_kept_and_not_silently_restored(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();
        $course = $this->completableCourse();

        $this->requireCourses($level, $area, $course);

        $user = $this->trainee();
        $admin = $this->admin();

        $this->complete($user, $course);

        $award = TraineeLevel::query()->where('user_id', $user->id)->sole();
        $award->update([
            'revoked_at' => now(),
            'revoked_by' => $admin->id,
            'revoked_reason' => 'Assessed against the wrong rubric.',
        ]);

        app(AwardCompetencyLevel::class)->forCourse($user->fresh(), $course);

        $this->assertSame(1, TraineeLevel::query()->where('user_id', $user->id)->count());
        $this->assertFalse($user->fresh()->holdsLevel($level, $area));
        $this->assertNull($user->fresh()->levelIn($area));
    }

    /**
     * The reporting question, asked from the other direction: given a rung and
     * an area, who holds it.
     */
    public function test_the_cohort_holding_a_level_in_an_area_can_be_listed(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();
        $course = $this->completableCourse();

        $this->requireCourses($level, $area, $course);

        $holder = $this->trainee();
        $other = $this->trainee();

        $this->complete($holder, $course);

        $ids = User::query()
            ->whereHas('competencyLevels', fn ($q) => $q->active()
                ->where('level_id', $level->id)
                ->where('competency_area_id', $area->id))
            ->pluck('id')
            ->all();

        $this->assertContains($holder->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_a_course_carries_its_level_and_area(): void
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();

        $course = Course::factory()->create([
            'level_id' => $level->id,
            'competency_area_id' => $area->id,
        ]);

        $this->assertSame($level->id, $course->level->id);
        $this->assertSame($area->id, $course->competencyArea->id);
        $this->assertTrue($level->courses->contains($course));
    }

    public function test_levels_and_areas_slug_themselves(): void
    {
        $level = Level::query()->create(['name' => 'Second Line', 'position' => 9]);
        $area = CompetencyArea::query()->create(['name' => 'Objects & Sensors']);

        $this->assertSame('second-line', $level->slug);
        $this->assertSame('objects-sensors', $area->slug);
    }
}
