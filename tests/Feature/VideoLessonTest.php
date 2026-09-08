<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Enums\LessonType;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Video lessons end to end (PA-9).
 *
 * The parser has its own unit tests; this covers what reaches the browser and
 * who may author it.
 */
class VideoTopicTest extends TestCase
{
    use RefreshDatabase;

    private function videoLesson(array $attributes = []): Lesson
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();

        return Lesson::factory()->for($module, 'module')->create([
            'type' => LessonType::VideoEmbed,
            'video_provider' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ',
            'video_duration_seconds' => 390,
            'video_transcript' => 'Open the object card, then the sensors tab.',
            ...$attributes,
        ]);
    }

    public function test_a_video_lesson_renders_for_an_enrolled_employee(): void
    {
        $lesson = $this->videoLesson();
        $user = $this->trainee();

        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $this->actingAs($user)
            ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('lesson.type', 'video_embed')
                ->where('lesson.video.provider', 'youtube')
                ->where('lesson.video.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1&playsinline=1')
                ->where('lesson.video_duration', '6:30')
                ->where('lesson.video_transcript', 'Open the object card, then the sensors tab.'));
    }

    /**
     * The embed URL is never read from storage — it is rebuilt from the
     * provider and id. Even if a row were tampered with directly, a bad id
     * yields no embed rather than an attacker-controlled iframe src.
     */
    public function test_a_tampered_video_id_produces_no_embed(): void
    {
        $lesson = $this->videoLesson();

        // Straight to the database, past the form validation.
        $lesson->forceFill(['video_id' => 'x" onload="alert(1)'])->saveQuietly();

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $this->actingAs($user)
            ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->where('lesson.video', null));
    }

    public function test_a_vimeo_lesson_uses_the_do_not_track_player(): void
    {
        $lesson = $this->videoLesson([
            'video_provider' => 'vimeo',
            'video_id' => '347119375',
        ]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $this->actingAs($user)
            ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('lesson.video.embed_url', 'https://player.vimeo.com/video/347119375?dnt=1&title=0&byline=0&portrait=0'));
    }

    public function test_a_lesson_with_no_video_set_still_renders(): void
    {
        $lesson = $this->videoLesson([
            'video_provider' => null,
            'video_id' => null,
            'video_transcript' => null,
        ]);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $this->actingAs($user)
            ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->where('lesson.video', null));
    }

    public function test_duration_is_formatted_and_the_length_ceiling_is_flagged(): void
    {
        $withinCeiling = $this->videoLesson(['video_duration_seconds' => 390]);
        $overCeiling = $this->videoLesson(['video_duration_seconds' => 605]);

        $this->assertSame('6:30', $withinCeiling->videoDurationForHumans());
        $this->assertFalse($withinCeiling->videoExceedsRecommendedLength());

        $this->assertSame('10:05', $overCeiling->videoDurationForHumans());
        $this->assertTrue($overCeiling->videoExceedsRecommendedLength());

        $this->assertNull($this->videoLesson(['video_duration_seconds' => null])->videoDurationForHumans());
    }

    /**
     * Video authoring is gated on videos.manage: Admin and Trainer hold it,
     * Trainee does not.
     *
     * The grant is per role, and that is deliberate. An early draft of the plan
     * called it "revocable per person"; it is not, because spatie has no
     * per-user deny and the permission is held by the role. Per-user grants were
     * considered and rejected — they would leave no single place to see what a
     * Trainer can do. The assertion below pins the role-level behaviour so a
     * future change to it is a decision rather than an accident.
     */
    public function test_video_authoring_follows_the_videos_manage_permission(): void
    {
        $admin = $this->admin();
        $trainer = $this->trainer();
        $trainee = $this->trainee();

        $this->assertTrue($admin->can('videos.manage'));
        $this->assertTrue($trainer->can('videos.manage'));
        $this->assertFalse($trainee->can('videos.manage'));

        // The grant is on the role, so a per-user revoke does not bite.
        $trainer->revokePermissionTo('videos.manage');
        $this->assertTrue(
            $trainer->fresh()->can('videos.manage'),
            'The permission comes from the Trainer role, so it survives a '
            .'per-user revoke. Taking it away means taking it from the role.',
        );
    }

    public function test_the_lesson_form_renders_for_a_video_lesson(): void
    {
        $lesson = $this->videoLesson();

        $this->actingAs($this->admin())
            ->get(LessonResource::getUrl('edit', ['record' => $lesson]))
            ->assertSuccessful();
    }

    public function test_video_is_an_offered_lesson_type(): void
    {
        $this->assertArrayHasKey('video_embed', LessonType::options());

        // The label names the method, because the author is choosing between
        // two of them in the same dropdown.
        $this->assertSame('Video (YouTube / Vimeo)', LessonType::VideoEmbed->label());

        $this->assertTrue(LessonType::VideoEmbed->isVideo());
        $this->assertFalse(LessonType::RichText->isVideo());
    }
}
