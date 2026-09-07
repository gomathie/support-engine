<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Native video upload (PA-10) — the second video method.
 *
 * The thing worth testing hardest is the boundary: the file has no public URL,
 * and the single route to it runs the lesson policy first. An employee who is
 * not enrolled must not be able to pull the bytes by knowing the lesson slug.
 */
class VideoUploadLessonTest extends TestCase
{
    use RefreshDatabase;

    private function uploadedVideoLesson(array $attributes = []): Lesson
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();

        $lesson = Lesson::factory()->for($module, 'module')->create([
            'type' => LessonType::VideoUpload,
            'video_disk' => 'private',
            'video_path' => 'lesson-videos/walkthrough.mp4',
            'video_original_name' => 'sensor walkthrough.mp4',
            'video_mime_type' => 'video/mp4',
            'video_size_bytes' => 41_943_040,
            'video_status' => 'ready',
            'video_duration_seconds' => 375,
            ...$attributes,
        ]);

        // 28 bytes — the Range assertion below depends on the exact length.
        if ($lesson->video_path) {
            Storage::disk('private')->put($lesson->video_path, 'not-really-a-video-but-bytes');
        }

        return $lesson->fresh();
    }

    public function test_an_enrolled_employee_can_stream_the_video(): void
    {
        $lesson = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $response = $this->actingAs($user)
            ->get(route('lessons.video', [$lesson->course->slug, $lesson->slug]));

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'video/mp4');

        // An inline upload that a browser might sniff as HTML is stored XSS.
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // Authorised per user — it must not land in a shared cache.
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    /**
     * The whole point of the private disk: knowing the URL is not authorisation.
     */
    public function test_an_employee_who_cannot_see_the_lesson_cannot_stream_it(): void
    {
        $lesson = $this->uploadedVideoLesson();

        // Not enrolled, and the course is not otherwise open to them.
        $outsider = $this->trainee();

        $this->actingAs($outsider)
            ->get(route('lessons.video', [$lesson->course->slug, $lesson->slug]))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_stream_the_video(): void
    {
        $lesson = $this->uploadedVideoLesson();

        $this->get(route('lessons.video', [$lesson->course->slug, $lesson->slug]))
            ->assertRedirect(route('login'));
    }

    /** Seeking in a <video> element sends Range; a 200 makes the scrubber useless. */
    public function test_range_requests_are_answered_with_partial_content(): void
    {
        $lesson = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $response = $this->actingAs($user)->get(
            route('lessons.video', [$lesson->course->slug, $lesson->slug]),
            ['Range' => 'bytes=0-9'],
        );

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 0-9/28');
        $response->assertHeader('Content-Length', '10');
    }

    /** A lesson pointing at a file that is not there is a 404, not a 500. */
    public function test_a_missing_file_is_not_found(): void
    {
        $lesson = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $lesson->course);

        Storage::disk('private')->delete($lesson->video_path);

        $this->actingAs($user)
            ->get(route('lessons.video', [$lesson->course->slug, $lesson->slug]))
            ->assertNotFound();
    }

    /** The route serves uploaded video and nothing else. */
    public function test_the_route_refuses_a_lesson_that_is_not_an_uploaded_video(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();

        $lesson = Lesson::factory()->for($module, 'module')->create([
            'type' => LessonType::RichText,
            'content' => '<p>Reading, not watching.</p>',
        ]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->get(route('lessons.video', [$course->slug, $lesson->slug]))
            ->assertNotFound();
    }

    /** A lesson id from one course must not be reachable through another. */
    public function test_a_lesson_from_another_course_is_not_found(): void
    {
        $lesson = $this->uploadedVideoLesson();
        $otherCourse = Course::factory()->create();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $lesson->course);
        app(EnrollEmployee::class)->handle($user, $otherCourse);

        $this->actingAs($user)
            ->get(route('lessons.video', [$otherCourse->slug, $lesson->slug]))
            ->assertNotFound();
    }

    public function test_the_lesson_page_carries_a_route_not_a_storage_path(): void
    {
        $lesson = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $this->actingAs($user)
            ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('lesson.type', 'video_upload')
                ->where('lesson.video_src', route('lessons.video', [$lesson->course->slug, $lesson->slug]))
                ->where('lesson.video_mime', 'video/mp4')
                ->where('lesson.video_duration', '6:15')

                // No embed for an uploaded file.
                ->where('lesson.video', null));
    }

    public function test_size_is_reported_against_the_cap(): void
    {
        $lesson = $this->uploadedVideoLesson();

        $this->assertSame('40 MB', $lesson->videoSizeForHumans());
        $this->assertTrue($lesson->hasUploadedVideo());

        $noFile = $this->uploadedVideoLesson(['video_path' => null, 'video_size_bytes' => null]);

        $this->assertNull($noFile->videoSizeForHumans());
        $this->assertFalse($noFile->hasUploadedVideo());
    }

    public function test_uploaded_video_is_an_offered_lesson_type(): void
    {
        $this->assertArrayHasKey('video_upload', LessonType::options());
        $this->assertTrue(LessonType::VideoUpload->isVideo());
        $this->assertTrue(LessonType::VideoUpload->isUploadedVideo());

        // An embed is a video, but not an uploaded one.
        $this->assertTrue(LessonType::VideoEmbed->isVideo());
        $this->assertFalse(LessonType::VideoEmbed->isUploadedVideo());
    }
}
