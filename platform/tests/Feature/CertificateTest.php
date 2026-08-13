<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteLesson;
use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Jobs\RenderCertificatePdf;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private function completableCourse(int $lessons = 2): Course
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();

        Lesson::factory()->count($lessons)->for($module, 'module')->create();

        return $course->fresh();
    }

    public function test_completing_a_course_issues_a_certificate(): void
    {
        Bus::fake();

        $course = $this->completableCourse();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        $certificate = Certificate::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $this->assertNotNull($certificate);
        $this->assertSame($user->certificateName(), $certificate->recipient_name);
        $this->assertSame($course->title, $certificate->course_title);
        $this->assertMatchesRegularExpression('/^PILOT-\d{4}-\d{6}$/', $certificate->certificate_number);

        Bus::assertDispatched(RenderCertificatePdf::class);
    }

    /**
     * Issuance runs from RecalculateCourseProgress, which fires on every lesson
     * tick — so it has to be safe to call repeatedly.
     */
    public function test_certificates_are_not_duplicated(): void
    {
        Bus::fake();

        $course = $this->completableCourse();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        // Tick one off and back on, forcing another recalculation.
        $first = $course->lessons()->first();
        app(CompleteLesson::class)->undo($user, $first);
        app(CompleteLesson::class)->handle($user, $first);

        $this->assertSame(1, Certificate::query()->where('user_id', $user->id)->count());
    }

    public function test_no_certificate_until_the_final_quiz_is_passed(): void
    {
        Bus::fake();

        $course = $this->completableCourse();

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
        ]);
        $question = QuizQuestion::factory()->for($quiz)->withOptions(2, [0])->create();

        $user = $this->employee();
        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        $this->assertSame(0, Certificate::query()->where('user_id', $user->id)->count());

        // Fail it — still no certificate.
        $wrong = $question->options()->where('is_correct', false)->first();
        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$wrong->id], 'text' => null],
        ]);

        $this->assertSame(0, Certificate::query()->where('user_id', $user->id)->count());

        // Pass it.
        $correct = $question->options()->where('is_correct', true)->first();
        $attempt = app(StartQuizAttempt::class)->handle($user, $quiz);
        app(GradeQuizAttempt::class)->handle($attempt, [
            ['question_id' => $question->id, 'option_ids' => [$correct->id], 'text' => null],
        ]);

        $this->assertSame(1, Certificate::query()->where('user_id', $user->id)->count());
    }

    public function test_the_pdf_is_rendered_to_the_private_disk(): void
    {
        Storage::fake('private');

        $course = $this->completableCourse();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        $certificate = Certificate::query()->where('user_id', $user->id)->firstOrFail();

        // The queue is sync in tests, so the job has already run.
        $certificate->refresh();

        $this->assertSame('private', $certificate->disk);
        Storage::disk('private')->assertExists($certificate->path);
    }

    public function test_an_employee_cannot_download_another_employees_certificate(): void
    {
        Storage::fake('private');

        $course = $this->completableCourse();
        $owner = $this->employee();

        app(EnrollEmployee::class)->handle($owner, $course);
        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($owner, $lesson);
        }

        $certificate = Certificate::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($this->employee())
            ->get(route('certificates.download', $certificate))
            ->assertForbidden();
    }

    public function test_the_owner_can_download_their_certificate(): void
    {
        Storage::fake('private');

        $course = $this->completableCourse();
        $owner = $this->employee();

        app(EnrollEmployee::class)->handle($owner, $course);
        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($owner, $lesson);
        }

        $certificate = Certificate::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('certificates.download', $certificate))
            ->assertOk();
    }

    // ─── PUBLIC VERIFICATION ─────────────────────────────────

    public function test_verification_works_without_signing_in(): void
    {
        Bus::fake();

        $course = $this->completableCourse();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);
        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        $certificate = Certificate::query()->where('user_id', $user->id)->firstOrFail();

        $this->get(route('certificates.verify', $certificate->verification_token))
            ->assertOk()
            ->assertSee($certificate->certificate_number);
    }

    /**
     * The public page must confirm the certificate without leaking anything
     * else about the employee.
     */
    public function test_verification_does_not_expose_score_or_email(): void
    {
        Bus::fake();

        $course = $this->completableCourse();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);
        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        $certificate = Certificate::query()->where('user_id', $user->id)->firstOrFail();

        $response = $this->get(route('certificates.verify', $certificate->verification_token));

        $response->assertDontSee($user->email);
        $response->assertDontSee('"score"', false);
    }

    public function test_an_unknown_token_reports_not_found(): void
    {
        $this->get(route('certificates.verify', 'nonsense'))
            ->assertOk()
            ->assertSee('"valid":false', false);
    }
}
