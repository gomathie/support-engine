<?php

namespace Tests\Feature;

use App\Actions\Competency\GradeRefresher;
use App\Actions\Competency\ScheduleRefreshers;
use App\Actions\Competency\StartRefresher;
use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteLesson;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Actions\Reporting\CalculateKpis;
use App\Enums\QuestionType;
use App\Enums\RefresherStatus;
use App\Models\CompetencyArea;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\LevelRequirement;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Refresher;
use App\Models\TraineeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Spaced-repetition refreshers (PA-18).
 *
 * §2 of the plan: "a 5-question refresher at 30 and 90 days, drawn from the
 * passed level's bank. Feeds the 90-day retention KPI."
 *
 * The thing worth testing hardest is not the marking — that mirrors the quiz
 * engine and is tested there — but the two properties that make the metric
 * mean anything: that the **baseline is frozen** at the moment of the award,
 * and that a **missed refresher is not a zero**. Both are easy to get wrong in
 * a way that produces a number rather than an error.
 */
class RefresherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Certificate rendering is queued on completion; irrelevant here.
        Bus::fake();
    }

    /**
     * A course with two lessons and a bank of auto-marked questions on a
     * published quiz, so a refresher has something to draw from.
     */
    private function courseWithBank(int $questions = 8): Course
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        Lesson::factory()->count(2)->for($module, 'module')->create();

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        QuizQuestion::factory()
            ->count($questions)
            ->withOptions()
            ->create(['quiz_id' => $quiz->id]);

        return $course->fresh();
    }

    private function awardLevel(User $user, Course $course): TraineeLevel
    {
        $level = Level::factory()->at(1)->create();
        $area = CompetencyArea::factory()->create();

        LevelRequirement::query()->create([
            'level_id' => $level->getKey(),
            'competency_area_id' => $area->getKey(),
            'course_id' => $course->getKey(),
        ]);

        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        // The bank quiz is a published module knowledge check, so it gates the
        // course. Passing it is what the level is awarded on — which is also
        // the realistic shape: a refresher draws from questions the trainee
        // actually sat.
        $this->passEveryCheck($user, $course);

        return TraineeLevel::query()->where('user_id', $user->id)->sole();
    }

    private function passEveryCheck(User $user, Course $course): void
    {
        $checks = Quiz::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->with('questions.options')
            ->get();

        foreach ($checks as $check) {
            if ($check->questions->isEmpty()) {
                continue;
            }

            $attempt = app(StartQuizAttempt::class)->handle($user, $check);

            app(GradeQuizAttempt::class)->handle(
                $attempt,
                $check->questions->map(fn (QuizQuestion $question) => [
                    'question_id' => $question->id,
                    'option_ids' => $question->options
                        ->where('is_correct', true)
                        ->pluck('id')
                        ->all(),
                    'text' => null,
                ])->all(),
            );
        }
    }

    // ─── SCHEDULING ──────────────────────────────────────────

    public function test_awarding_a_level_schedules_a_30_and_a_90_day_refresher(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $refreshers = Refresher::query()->where('trainee_level_id', $award->id)->get();

        $this->assertCount(2, $refreshers);
        $this->assertEqualsCanonicalizing([30, 90], $refreshers->pluck('interval_days')->all());

        $thirty = $refreshers->firstWhere('interval_days', 30);

        $this->assertSame(
            $award->awarded_at->addDays(30)->toDateString(),
            $thirty->due_at->toDateString(),
        );
        $this->assertSame(RefresherStatus::Scheduled, $thirty->status);
    }

    /**
     * Scheduling runs inside the award, which runs on every progress
     * recalculation — every lesson tick. It has to be safe to repeat.
     */
    public function test_scheduling_is_idempotent(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        app(ScheduleRefreshers::class)->handle($award);
        app(ScheduleRefreshers::class)->handle($award);

        $this->assertSame(2, Refresher::query()->where('trainee_level_id', $award->id)->count());
    }

    /** Nothing to refresh, and asking would be worse than saying nothing. */
    public function test_a_revoked_award_is_not_refreshed(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        Refresher::query()->where('trainee_level_id', $award->id)->delete();
        $award->forceFill(['revoked_at' => now()])->save();

        $this->assertCount(0, app(ScheduleRefreshers::class)->handle($award->fresh()));
    }

    // ─── SITTING ONE ─────────────────────────────────────────

    public function test_it_draws_five_questions_from_the_levels_bank(): void
    {
        $user = $this->trainee();
        $course = $this->courseWithBank(8);
        $award = $this->awardLevel($user, $course);

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);

        $paper = app(StartRefresher::class)->handle($refresher);

        $this->assertCount(Refresher::QUESTION_COUNT, $paper);

        $bankIds = QuizQuestion::query()
            ->whereHas('quiz', fn ($q) => $q->where('course_id', $course->id))
            ->pluck('id');

        foreach ($paper as $answer) {
            $this->assertTrue($bankIds->contains($answer->quiz_question_id));
        }
    }

    /** Reloading the page must not hand somebody a different paper. */
    public function test_reopening_returns_the_same_five_questions(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank(20));

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);

        $first = app(StartRefresher::class)->handle($refresher)->pluck('quiz_question_id')->sort()->values();
        $second = app(StartRefresher::class)->handle($refresher->fresh())->pluck('quiz_question_id')->sort()->values();

        $this->assertEquals($first->all(), $second->all());
    }

    /**
     * A written question would put a trainer on the marking queue for every
     * refresher of every trainee, twice each — and KPI 7 already watches
     * trainer workload as a burnout signal.
     */
    public function test_it_never_draws_a_question_that_needs_a_human_to_mark_it(): void
    {
        $user = $this->trainee();
        $course = $this->courseWithBank(3);
        $award = $this->awardLevel($user, $course);

        // The bank grows after the award — a written paper is added to the same
        // course. It must never appear on a refresher.
        $quiz = Quiz::query()->where('course_id', $course->id)->sole();

        QuizQuestion::factory()->count(5)->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::Written,
        ]);

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);

        $drawn = app(StartRefresher::class)->draw($refresher);

        $this->assertCount(3, $drawn, 'Only the three auto-marked questions are eligible.');
        $this->assertTrue($drawn->every(fn ($q) => ! $q->type->requiresManualGrading()));
    }

    // ─── MARKING ─────────────────────────────────────────────

    public function test_it_marks_all_or_nothing_and_records_retention(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        // A known original score to be a percentage of.
        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 90);
        $refresher->forceFill(['baseline_score' => 80])->save();

        $paper = app(StartRefresher::class)->handle($refresher);

        // Answer the first three correctly and leave two blank: 60%.
        $submissions = $paper->values()->map(function ($answer, $index) {
            $correct = $answer->question->options->firstWhere('is_correct', true);

            return [
                'question_id' => $answer->quiz_question_id,
                'option_ids' => $index < 3 ? [$correct->id] : [],
            ];
        })->all();

        $graded = app(GradeRefresher::class)->handle($refresher, $submissions);

        $this->assertSame(RefresherStatus::Completed, $graded->status);
        $this->assertSame('60.00', $graded->score);

        // 60 of an original 80 is 75% retained.
        $this->assertSame('75.00', $graded->retention);
        $this->assertNotNull($graded->completed_at);
    }

    /**
     * A hand-granted level has no exam behind it. There is nothing to be a
     * percentage of, and reporting that as zero retention would be a fabricated
     * collapse.
     */
    public function test_no_baseline_means_no_retention_rather_than_zero(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 90);
        $refresher->forceFill(['baseline_score' => null])->save();

        $paper = app(StartRefresher::class)->handle($refresher);

        $graded = app(GradeRefresher::class)->handle(
            $refresher,
            $paper->map(fn ($a) => ['question_id' => $a->quiz_question_id, 'option_ids' => []])->all(),
        );

        $this->assertSame('0.00', $graded->score);
        $this->assertNull($graded->retention);
    }

    // ─── THE WINDOW ──────────────────────────────────────────

    public function test_a_refresher_cannot_be_sat_before_it_is_due(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);

        $this->assertFalse($refresher->isOpen());

        $this->actingAs($user)
            ->get(route('refreshers.show', $refresher))
            ->assertForbidden();
    }

    public function test_a_refresher_closes_after_its_window(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);
        $refresher->forceFill(['due_at' => now()->subDays(Refresher::WINDOW_DAYS + 1)])->save();

        $this->assertTrue($refresher->hasLapsed());
        $this->assertFalse($refresher->isOpen());

        $this->actingAs($user)
            ->get(route('refreshers.show', $refresher))
            ->assertForbidden();
    }

    /**
     * Left as `scheduled` for ever, a lapsed refresher both nags the trainee
     * with a stale link and hides how much of the cohort KPI 6 was computed
     * from.
     */
    public function test_the_sweep_closes_a_lapsed_refresher_and_leaves_a_live_one_alone(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $lapsed = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);
        $lapsed->forceFill(['due_at' => now()->subDays(Refresher::WINDOW_DAYS + 1)])->save();

        $live = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 90);
        $live->forceFill(['due_at' => now()->subDay()])->save();

        $this->artisan('training:close-lapsed-refreshers')->assertSuccessful();

        $this->assertSame(RefresherStatus::Missed, $lapsed->fresh()->status);
        $this->assertSame(RefresherStatus::Scheduled, $live->fresh()->status);
        $this->assertNull($lapsed->fresh()->score, 'A missed refresher is not scored.');
    }

    // ─── ACCESS ──────────────────────────────────────────────

    public function test_only_the_owner_may_sit_it(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);
        $refresher->forceFill(['due_at' => now()->subDay()])->save();

        // The owner can.
        $this->actingAs($user)
            ->get(route('refreshers.show', $refresher))
            ->assertSuccessful();

        // Nobody else, and that includes an admin — opening somebody's paper
        // means being able to submit answers as them.
        $this->actingAs($this->trainee())
            ->get(route('refreshers.show', $refresher))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('refreshers.show', $refresher))
            ->assertForbidden();
    }

    /** The answer key must not be in the page. */
    public function test_the_paper_carries_no_answer_key(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);
        $refresher->forceFill(['due_at' => now()->subDay()])->save();

        $this->actingAs($user)
            ->get(route('refreshers.show', $refresher))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('questions', Refresher::QUESTION_COUNT)
                ->missing('questions.0.options.0.is_correct')
                ->missing('questions.0.explanation'));
    }

    public function test_it_appears_on_the_dashboard_once_due(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->has('refreshers_due', 0));

        Refresher::query()
            ->where('trainee_level_id', $award->id)
            ->where('interval_days', 30)
            ->update(['due_at' => now()->subDay()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->has('refreshers_due', 1));
    }

    // ─── KPI 6 ───────────────────────────────────────────────

    /**
     * The metric this whole ticket exists for. It had been reported as "not
     * measurable" since the KPI dashboard was built.
     */
    public function test_kpi_6_reports_once_a_90_day_refresher_has_been_sat(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $before = app(CalculateKpis::class)->handle()->firstWhere('number', 6);
        $this->assertSame('awaiting_data', $before['status']);
        $this->assertNull($before['value']);

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 90);
        $refresher->forceFill(['baseline_score' => 80])->save();

        $paper = app(StartRefresher::class)->handle($refresher);

        app(GradeRefresher::class)->handle(
            $refresher,
            $paper->values()->map(fn ($a) => [
                'question_id' => $a->quiz_question_id,
                'option_ids' => [$a->question->options->firstWhere('is_correct', true)->id],
            ])->all(),
        );

        $after = app(CalculateKpis::class)->handle()->firstWhere('number', 6);

        // 100% of an original 80% is 125% retained — above the 75% target.
        $this->assertSame('125%', $after['value']);
        $this->assertSame('on_target', $after['status']);
        $this->assertStringContainsString('1 sat', $after['sample']);
    }

    /**
     * A refresher nobody sat measures the process, not the person. Averaging it
     * in as nought would report a collapse in retention that has not been
     * observed anywhere.
     */
    public function test_a_missed_refresher_is_not_counted_as_zero(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 90);
        $refresher->forceFill([
            'baseline_score' => 80,
            'status' => RefresherStatus::Missed,
        ])->save();

        $kpi = app(CalculateKpis::class)->handle()->firstWhere('number', 6);

        $this->assertNull($kpi['value'], 'A missed refresher must not produce a retention figure.');
        $this->assertSame('awaiting_data', $kpi['status']);
    }

    /** The 30-day sitting must not flatter a metric named for ninety days. */
    public function test_kpi_6_ignores_the_30_day_refresher(): void
    {
        $user = $this->trainee();
        $award = $this->awardLevel($user, $this->courseWithBank());

        $thirty = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);
        $thirty->forceFill(['baseline_score' => 80])->save();

        $paper = app(StartRefresher::class)->handle($thirty);

        app(GradeRefresher::class)->handle(
            $thirty,
            $paper->values()->map(fn ($a) => [
                'question_id' => $a->quiz_question_id,
                'option_ids' => [$a->question->options->firstWhere('is_correct', true)->id],
            ])->all(),
        );

        $kpi = app(CalculateKpis::class)->handle()->firstWhere('number', 6);

        $this->assertNull($kpi['value'], 'Only the 90-day refresher feeds the 90-day metric.');
    }

    // ─── THE THING THAT MUST NOT HAPPEN ──────────────────────

    /**
     * Refreshers must gate nothing.
     *
     * The obvious implementation is a `Quiz` row, and it would have been a
     * disaster: a course-scoped quiz with no module and no lesson *is* the
     * course's final exam to `Course::finalQuizzes()` and
     * `RecalculateCourseProgress`, so every refresher would have silently
     * re-opened a course the trainee finished three months earlier. Three bugs
     * of exactly that shape were fixed the same day this was built.
     */
    public function test_a_refresher_does_not_re_gate_a_finished_course(): void
    {
        $user = $this->trainee();
        $course = $this->courseWithBank();
        $award = $this->awardLevel($user, $course);

        $this->assertTrue($user->fresh()->holdsLevel($award->level, $award->competencyArea));

        $refresher = Refresher::query()->where('trainee_level_id', $award->id)->firstWhere('interval_days', 30);
        app(StartRefresher::class)->handle($refresher);

        // Nothing about an outstanding refresher touches the quiz tables.
        $this->assertSame(
            1,
            Quiz::query()->where('course_id', $course->id)->count(),
            'A refresher must not create a quiz.',
        );

        // And the level is still held, with the course still complete.
        $this->assertTrue($user->fresh()->holdsLevel($award->level, $award->competencyArea));
    }
}
