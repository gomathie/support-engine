<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteLesson;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * A course may have several final exams, and all of them must be passed.
 *
 * The PILOT examination is three papers — A, B and C — and all three are final
 * exams rather than module quizzes. That only works because completion requires
 * *every* course-scoped quiz.
 *
 * The old rule read `finalQuiz()->first()`, so a second final exam was sittable
 * and counted for nothing. That is how the official 40-question Section A sat in
 * front of trainees for weeks deciding nothing at all.
 */
class MultipleFinalExamsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** A course with one readable lesson and two final exams. */
    private function courseWithTwoFinals(): array
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        Lesson::factory()->for($module, 'module')->create();

        $papers = [];

        foreach (['Paper one', 'Paper two'] as $title) {
            $quiz = Quiz::factory()->create([
                'course_id' => $course->id,
                'module_id' => null,
                'lesson_id' => null,
                'title' => $title,
                'passing_score' => 70,
                'max_attempts' => 2,
                'is_published' => true,
            ]);

            $papers[] = [
                $quiz,
                QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create(['points' => 1]),
            ];
        }

        return [$course->fresh(), $papers];
    }

    private function sit(User $user, Quiz $quiz, QuizQuestion $question, bool $correctly): void
    {
        $option = $question->options()->where('is_correct', $correctly)->first();

        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);

        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$option->id], 'text' => null],
        ]);
    }

    private function readEverything(User $user, Course $course): void
    {
        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }
    }

    private function statusFor(User $user, Course $course): ?ProgressStatus
    {
        return $user->courseProgress()->where('course_id', $course->id)->first()?->status;
    }

    // ─── ALL OF THEM, NOT THE FIRST ──────────────────────────

    public function test_passing_only_one_of_two_finals_does_not_complete_the_course(): void
    {
        [$course, $papers] = $this->courseWithTwoFinals();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        [$first, $firstQuestion] = $papers[0];
        $this->sit($user, $first, $firstQuestion, correctly: true);

        $this->assertSame(
            ProgressStatus::InProgress,
            $this->statusFor($user->fresh(), $course),
            'A second final exam must count. Reading only the first is how one gets ignored.',
        );
    }

    public function test_passing_every_final_completes_the_course(): void
    {
        [$course, $papers] = $this->courseWithTwoFinals();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        foreach ($papers as [$quiz, $question]) {
            $this->sit($user, $quiz, $question, correctly: true);
        }

        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user->fresh(), $course));
    }

    public function test_failing_the_second_paper_keeps_the_course_open(): void
    {
        [$course, $papers] = $this->courseWithTwoFinals();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        $this->sit($user, $papers[0][0], $papers[0][1], correctly: true);
        $this->sit($user, $papers[1][0], $papers[1][1], correctly: false);

        $this->assertSame(ProgressStatus::InProgress, $this->statusFor($user->fresh(), $course));
    }

    /** Out of attempts on any paper is terminal, not just on the first. */
    public function test_exhausting_attempts_on_the_second_paper_fails_the_course(): void
    {
        [$course, $papers] = $this->courseWithTwoFinals();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        $this->sit($user, $papers[0][0], $papers[0][1], correctly: true);

        // max_attempts is 2.
        $this->sit($user, $papers[1][0], $papers[1][1], correctly: false);
        $this->sit($user, $papers[1][0], $papers[1][1], correctly: false);

        $this->assertSame(ProgressStatus::Failed, $this->statusFor($user->fresh(), $course));
    }

    /** An unpublished paper is not a gate — nobody can sit it. */
    public function test_an_unpublished_final_does_not_block_completion(): void
    {
        [$course, $papers] = $this->courseWithTwoFinals();

        $papers[1][0]->update(['is_published' => false]);

        $user = $this->trainee();
        $this->readEverything($user, $course);
        $this->sit($user, $papers[0][0], $papers[0][1], correctly: true);

        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user->fresh(), $course));
    }

    // ─── THE REPORTED SCORE ──────────────────────────────────

    /**
     * `final_score` goes onto the certificate and the training report, so with
     * several papers it has to mean something. It is the mean of the best
     * attempt on each — one number that moves when any paper improves.
     */
    public function test_the_recorded_score_averages_the_papers(): void
    {
        [$course, $papers] = $this->courseWithTwoFinals();
        $user = $this->trainee();

        $this->readEverything($user, $course);

        $this->sit($user, $papers[0][0], $papers[0][1], correctly: true);   // 100
        $this->sit($user, $papers[1][0], $papers[1][1], correctly: false);  // 0

        $progress = $user->courseProgress()->where('course_id', $course->id)->first();

        $this->assertSame(50.0, (float) $progress->final_score);

        // Two sittings across the two papers.
        $this->assertSame(2, $progress->quiz_attempts_count);
    }

    /** A course with no final exam at all still completes on its content. */
    public function test_a_course_without_finals_is_unaffected(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();
        Lesson::factory()->count(2)->for($module, 'module')->create();

        $user = $this->trainee();
        $this->readEverything($user, $course->fresh());

        $this->assertSame(ProgressStatus::Completed, $this->statusFor($user, $course));
    }
}
