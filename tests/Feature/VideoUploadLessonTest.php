<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Enums\TopicType;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Native video upload (PA-10) — the second video method.
 *
 * The thing worth testing hardest is the boundary: the file has no public URL,
 * and the single route to it runs the topic policy first. An employee who is
 * not enrolled must not be able to pull the bytes by knowing the topic slug.
 */
class VideoUploadLessonTest extends TestCase
{
    use RefreshDatabase;

    private function uploadedVideoLesson(array $attributes = []): Topic
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        $topic = Topic::factory()->for($lesson, 'lesson')->create([
            'type' => TopicType::VideoUpload,
            'video_disk' => 'private',
            'video_path' => 'topic-videos/walkthrough.mp4',
            'video_original_name' => 'sensor walkthrough.mp4',
            'video_mime_type' => 'video/mp4',
            'video_size_bytes' => 41_943_040,
            'video_status' => 'ready',
            'video_duration_seconds' => 375,
            ...$attributes,
        ]);

        // 28 bytes — the Range assertion below depends on the exact length.
        if ($topic->video_path) {
            Storage::disk('private')->put($topic->video_path, 'not-really-a-video-but-bytes');
        }

        return $topic->fresh();
    }

    public function test_an_enrolled_employee_can_stream_the_video(): void
    {
        $topic = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $topic->course);

        $response = $this->actingAs($user)
            ->get(route('topics.video', [$topic->course->slug, $topic->slug]));

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
        $topic = $this->uploadedVideoLesson();

        // Not enrolled, and the course is not otherwise open to them.
        $outsider = $this->trainee();

        $this->actingAs($outsider)
            ->get(route('topics.video', [$topic->course->slug, $topic->slug]))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_stream_the_video(): void
    {
        $topic = $this->uploadedVideoLesson();

        $this->get(route('topics.video', [$topic->course->slug, $topic->slug]))
            ->assertRedirect(route('login'));
    }

    /** Seeking in a <video> element sends Range; a 200 makes the scrubber useless. */
    public function test_range_requests_are_answered_with_partial_content(): void
    {
        $topic = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $topic->course);

        $response = $this->actingAs($user)->get(
            route('topics.video', [$topic->course->slug, $topic->slug]),
            ['Range' => 'bytes=0-9'],
        );

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 0-9/28');
        $response->assertHeader('Content-Length', '10');
    }

    /** A topic pointing at a file that is not there is a 404, not a 500. */
    public function test_a_missing_file_is_not_found(): void
    {
        $topic = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $topic->course);

        Storage::disk('private')->delete($topic->video_path);

        $this->actingAs($user)
            ->get(route('topics.video', [$topic->course->slug, $topic->slug]))
            ->assertNotFound();
    }

    /** The route serves uploaded video and nothing else. */
    public function test_the_route_refuses_a_lesson_that_is_not_an_uploaded_video(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        $topic = Topic::factory()->for($lesson, 'lesson')->create([
            'type' => TopicType::RichText,
            'content' => '<p>Reading, not watching.</p>',
        ]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->get(route('topics.video', [$course->slug, $topic->slug]))
            ->assertNotFound();
    }

    /** A topic id from one course must not be reachable through another. */
    public function test_a_lesson_from_another_course_is_not_found(): void
    {
        $topic = $this->uploadedVideoLesson();
        $otherCourse = Course::factory()->create();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $topic->course);
        app(EnrollEmployee::class)->handle($user, $otherCourse);

        $this->actingAs($user)
            ->get(route('topics.video', [$otherCourse->slug, $topic->slug]))
            ->assertNotFound();
    }

    public function test_the_lesson_page_carries_a_route_not_a_storage_path(): void
    {
        $topic = $this->uploadedVideoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $topic->course);

        $this->actingAs($user)
            ->get(route('topics.show', [$topic->course->slug, $topic->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('topic.type', 'video_upload')
                ->where('topic.video_src', route('topics.video', [$topic->course->slug, $topic->slug]))
                ->where('topic.video_mime', 'video/mp4')
                ->where('topic.video_duration', '6:15')

                // No embed for an uploaded file.
                ->where('topic.video', null));
    }

    public function test_size_is_reported_against_the_cap(): void
    {
        $topic = $this->uploadedVideoLesson();

        $this->assertSame('40 MB', $topic->videoSizeForHumans());
        $this->assertTrue($topic->hasUploadedVideo());

        $noFile = $this->uploadedVideoLesson(['video_path' => null, 'video_size_bytes' => null]);

        $this->assertNull($noFile->videoSizeForHumans());
        $this->assertFalse($noFile->hasUploadedVideo());
    }

    public function test_uploaded_video_is_an_offered_lesson_type(): void
    {
        $this->assertArrayHasKey('video_upload', TopicType::options());
        $this->assertTrue(TopicType::VideoUpload->isVideo());
        $this->assertTrue(TopicType::VideoUpload->isUploadedVideo());

        // An embed is a video, but not an uploaded one.
        $this->assertTrue(TopicType::VideoEmbed->isVideo());
        $this->assertFalse(TopicType::VideoEmbed->isUploadedVideo());
    }
}
