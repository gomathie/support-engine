<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Practical\GradePracticalSubmission;
use App\Actions\Practical\SubmitPracticalTask;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Actions\Reporting\CalculateKpis;
use App\Enums\RubricCriterion;
use App\Filament\Pages\Kpis;
use App\Models\Course;
use App\Models\PracticalTask;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * The success metrics (PA-19).
 *
 * The behaviour worth pinning is not the arithmetic — it is the honesty: a
 * metric that cannot be computed says so and says why, rather than reporting
 * zero, and "too easy" is reported as a finding rather than a success.
 */
class KpiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    private function kpi(int $number): array
    {
        return app(CalculateKpis::class)->handle()->firstWhere('number', $number);
    }

    /** A quiz sat once by each of the given people, passing or failing. */
    private function sitExam(array $outcomes): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id, 'passing_score' => 50]);

        $question = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create(['points' => 1]);

        $correct = $question->options()->where('is_correct', true)->first()->id;
        $wrong = $question->options()->where('is_correct', false)->first()->id;

        foreach ($outcomes as $shouldPass) {
            $user = $this->trainee();
            app(EnrollEmployee::class)->handle($user, $course);

            $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

            app(GradeQuizAttempt::class)->handle($attempt, [
                [
                    'question_id' => $question->id,
                    'option_ids' => [$shouldPass ? $correct : $wrong],
                    'text' => null,
                ],
            ]);
        }
    }

    // ─── HONESTY ─────────────────────────────────────────────

    /**
     * Four of the nine depend on work that does not exist. They must say so —
     * a dashboard that silently shows five of nine looks complete when it is
     * not, and the missing four are the ones blocked on content.
     */
    public function test_unmeasurable_metrics_say_so_and_say_why(): void
    {
        $kpis = app(CalculateKpis::class)->handle();

        $this->assertCount(9, $kpis, 'All nine KPIs from §7 must be represented.');

        $blocked = $kpis->where('status', 'not_measurable');

        $this->assertCount(3, $blocked);

        foreach ($blocked as $kpi) {
            $this->assertNull($kpi['value'], $kpi['label'].' must not report a number it cannot compute.');
            $this->assertNotEmpty($kpi['note'], $kpi['label'].' must explain what is missing.');
        }

        /*
         * The three, named, so a later change that quietly starts faking one
         * of them fails here.
         *
         * KPI 6 (90-day retention) was on this list until the refreshers
         * arrived (PA-18). It is now merely *awaiting data* — it can be
         * computed the moment somebody sits a 90-day refresher, which is a
         * different and much better state than structurally impossible.
         */
        $this->assertSame(
            [2, 3, 4],
            $blocked->pluck('number')->sort()->values()->all(),
        );
    }

    /** Completion rate is the metric this whole exercise exists to move away from. */
    public function test_completion_rate_is_not_among_the_metrics(): void
    {
        $labels = app(CalculateKpis::class)->handle()->pluck('label')->implode(' | ');

        $this->assertStringNotContainsStringIgnoringCase('completion rate', $labels);
    }

    public function test_a_metric_with_no_data_yet_is_distinct_from_one_that_cannot_be_measured(): void
    {
        // Nothing has been sat, so KPI 1 is measurable but has no data.
        $this->assertSame('awaiting_data', $this->kpi(1)['status']);

        // KPI 4 is structurally impossible today, whatever the data.
        $this->assertSame('not_measurable', $this->kpi(4)['status']);
    }

    // ─── KPI 1: the band ─────────────────────────────────────

    /**
     * The target is a band, and both ends are findings. A 100% first-time pass
     * rate means the exam is too easy — reporting it as success would be the
     * opposite of what §7 asks for.
     */
    public function test_an_easy_exam_is_reported_as_above_the_band_not_as_success(): void
    {
        $this->sitExam([true, true, true, true]);

        $kpi = $this->kpi(1);

        $this->assertSame('100%', $kpi['value']);
        $this->assertSame('above', $kpi['status']);
        $this->assertStringContainsString('too easy', $kpi['note']);
    }

    public function test_a_low_pass_rate_points_at_the_content_not_the_cohort(): void
    {
        $this->sitExam([true, false, false, false]);

        $kpi = $this->kpi(1);

        $this->assertSame('25%', $kpi['value']);
        $this->assertSame('below', $kpi['status']);
        $this->assertStringContainsString('does not teach', $kpi['note']);
    }

    public function test_a_rate_inside_the_band_is_on_target(): void
    {
        // 3 of 4 = 75%, inside 65–80.
        $this->sitExam([true, true, true, false]);

        $kpi = $this->kpi(1);

        $this->assertSame('75%', $kpi['value']);
        $this->assertSame('on_target', $kpi['status']);
    }

    /** Only first sittings count, or a determined retaker would flatter the number. */
    public function test_only_first_attempts_count_towards_the_first_time_pass_rate(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 50,
            'max_attempts' => 3,
        ]);

        $question = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create(['points' => 1]);
        $correct = $question->options()->where('is_correct', true)->first()->id;
        $wrong = $question->options()->where('is_correct', false)->first()->id;

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        // Fails first, passes second.
        foreach ([$wrong, $correct] as $choice) {
            $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

            app(GradeQuizAttempt::class)->handle($attempt, [
                ['question_id' => $question->id, 'option_ids' => [$choice], 'text' => null],
            ]);
        }

        $kpi = $this->kpi(1);

        $this->assertSame('0%', $kpi['value'], 'The later pass must not rewrite the first-time rate.');
        $this->assertStringContainsString('1 first attempts', $kpi['sample']);
    }

    // ─── KPI 5: practical quality ────────────────────────────

    public function test_practical_quality_is_the_mean_rubric_total(): void
    {
        $task = PracticalTask::query()->create([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Calibrate a sensor',
            'brief' => '<p>Do it.</p>',
            'is_published' => true,
        ]);

        foreach ([[4, 4, 4, 4], [3, 3, 3, 3]] as $scores) {
            $trainee = $this->trainee();
            $draft = app(SubmitPracticalTask::class)->draftFor($trainee, $task);
            $submission = app(SubmitPracticalTask::class)->submit($draft, 'A write-up that is long enough.');

            app(GradePracticalSubmission::class)->handle($submission, $this->admin(), [
                RubricCriterion::Correctness->value => $scores[0],
                RubricCriterion::Method->value => $scores[1],
                RubricCriterion::Verification->value => $scores[2],
                RubricCriterion::Communication->value => $scores[3],
            ]);
        }

        $kpi = $this->kpi(5);

        // 16 and 12 → 14.
        $this->assertSame('14 / 16', $kpi['value']);
        $this->assertSame('on_target', $kpi['status']);
    }

    // ─── KPI 7: the proxy ────────────────────────────────────

    /**
     * The plan asks for hours per week. Nothing times marking, so the screen
     * reports queue size and labels itself a proxy rather than inventing the
     * number its own target is checked against.
     */
    public function test_trainer_workload_is_labelled_a_proxy_rather_than_faking_hours(): void
    {
        $this->trainer();

        $kpi = $this->kpi(7);

        $this->assertSame('proxy', $kpi['status']);
        $this->assertStringContainsString('items', $kpi['value']);
        $this->assertStringContainsString('proxy', strtolower($kpi['note']));
    }

    // ─── KPI 9: item difficulty ──────────────────────────────

    /** Too few answers is not evidence, so those questions are left out. */
    public function test_a_question_with_too_few_answers_is_not_judged(): void
    {
        $this->sitExam([false, false]);

        $this->assertTrue(
            app(CalculateKpis::class)->questionDifficulties()->isEmpty(),
            'Two answers should not be enough to call a question defective.',
        );
    }

    public function test_a_question_nobody_passes_is_flagged_as_a_content_defect(): void
    {
        $this->sitExam([false, false, false, false, false, false]);

        $questions = app(CalculateKpis::class)->questionDifficulties();

        $this->assertCount(1, $questions);
        $this->assertSame(0.0, $questions->first()['pass_rate']);
        $this->assertTrue($questions->first()['flagged']);
        $this->assertStringContainsString('defective question', $questions->first()['verdict']);

        $this->assertSame('1 of 1 flagged', $this->kpi(9)['value']);
    }

    public function test_a_question_everybody_passes_is_flagged_as_not_discriminating(): void
    {
        $this->sitExam([true, true, true, true, true, true]);

        $question = app(CalculateKpis::class)->questionDifficulties()->first();

        $this->assertSame(100.0, $question['pass_rate']);
        $this->assertTrue($question['flagged']);
        $this->assertStringContainsString('not discriminating', $question['verdict']);
    }

    // ─── ACCESS ──────────────────────────────────────────────

    public function test_the_page_renders_for_an_admin(): void
    {
        $this->sitExam([true, false, false, false, false, false]);

        $this->actingAs($this->admin())
            ->get(Kpis::getUrl())
            ->assertSuccessful()
            ->assertSee('First-time pass rate')
            ->assertSee('Questions at the extremes');
    }

    /** Org-wide metrics, including how much work colleagues are holding. */
    public function test_a_trainer_cannot_reach_the_metrics(): void
    {
        $this->assertFalse(Kpis::canAccess());

        $this->actingAs($this->trainer())
            ->get(Kpis::getUrl())
            ->assertForbidden();
    }

    public function test_coverage_counts_what_is_actually_reporting(): void
    {
        $this->sitExam([true, false, false, false]);

        $coverage = (new Kpis())->coverage();

        $this->assertSame(9, $coverage['total']);

        // Three, not four: KPI 6 became measurable when refreshers arrived.
        $this->assertSame(3, $coverage['blocked']);

        $this->assertGreaterThan(0, $coverage['reporting']);
    }
}
