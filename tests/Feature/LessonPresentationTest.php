<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Enums\LessonType;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What a lesson looks like before its body: a summary, a cover image, and the
 * documentation it was written from.
 *
 * The one part worth testing hard is the doc links. They are author-supplied
 * URLs rendered into an `href`, and an `href` runs a `javascript:` URL on
 * click — so the model, not the template, decides what is renderable.
 */
class LessonPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(array $attributes = []): Lesson
    {
        $course = Course::factory()->create();
        $module = Module::factory()->for($course)->create();

        return Lesson::factory()->for($module, 'module')->create([
            'type' => LessonType::RichText,
            'content' => '<p>The lesson body.</p>',
            ...$attributes,
        ]);
    }

    private function openLesson(Lesson $lesson): \Illuminate\Testing\TestResponse
    {
        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $lesson->course);

        return $this->actingAs($user)
            ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]));
    }

    public function test_the_lesson_page_carries_its_summary_and_cover(): void
    {
        Storage::fake('public');

        $lesson = $this->lesson([
            'summary' => 'Create an object and attach its first sensor.',
            'cover_image_path' => 'lesson-covers/objects.png',
        ]);

        $this->openLesson($lesson)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('lesson.summary', 'Create an object and attach its first sensor.')
                ->where('lesson.cover_image', Storage::disk('public')->url('lesson-covers/objects.png')));
    }

    public function test_a_lesson_without_a_cover_reports_null_rather_than_a_broken_url(): void
    {
        $lesson = $this->lesson(['cover_image_path' => null]);

        $this->assertNull($lesson->coverImageUrl());

        $this->openLesson($lesson)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->where('lesson.cover_image', null));
    }

    public function test_documentation_links_reach_the_page(): void
    {
        $lesson = $this->lesson([
            'doc_links' => [
                ['title' => 'Objects', 'url' => 'https://docs.pilot-gps.com/objects.html'],
                ['title' => 'Sensors', 'url' => 'http://docs.pilot-gps.com/sensors_1.html'],
            ],
        ]);

        $this->openLesson($lesson)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('lesson.doc_links.0.title', 'Objects')
                ->where('lesson.doc_links.0.url', 'https://docs.pilot-gps.com/objects.html')
                ->where('lesson.doc_links.1.url', 'http://docs.pilot-gps.com/sensors_1.html')
                ->count('lesson.doc_links', 2));
    }

    /**
     * The form refuses these, but the form is not the only way a row is
     * written: a seeder, a console command or a tampered row all reach the
     * same template. The filter belongs where the data is read.
     */
    public function test_a_script_url_never_reaches_an_href(): void
    {
        $lesson = $this->lesson([
            'doc_links' => [
                ['title' => 'Legitimate', 'url' => 'https://docs.pilot-gps.com/objects.html'],
                ['title' => 'Click me', 'url' => 'javascript:alert(document.cookie)'],
                ['title' => 'Also me', 'url' => 'JavaScript:alert(1)'],
                ['title' => 'Inline', 'url' => 'data:text/html,<script>alert(1)</script>'],
                ['title' => 'Local file', 'url' => 'file:///etc/passwd'],
            ],
        ]);

        $links = $lesson->documentationLinks();

        $this->assertCount(1, $links, 'Only the http(s) link survives.');
        $this->assertSame('https://docs.pilot-gps.com/objects.html', $links[0]['url']);

        $this->openLesson($lesson)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->count('lesson.doc_links', 1));
    }

    public function test_half_filled_and_missing_links_are_dropped(): void
    {
        $lesson = $this->lesson([
            'doc_links' => [
                ['title' => 'No URL', 'url' => ''],
                ['title' => '', 'url' => 'https://docs.pilot-gps.com/objects.html'],
                ['title' => 'Whitespace', 'url' => '   '],
                ['title' => '  Trimmed  ', 'url' => '  https://docs.pilot-gps.com/panel.html  '],
            ],
        ]);

        $links = $lesson->documentationLinks();

        $this->assertSame(
            [['title' => 'Trimmed', 'url' => 'https://docs.pilot-gps.com/panel.html']],
            $links,
        );
    }

    public function test_a_lesson_with_no_links_reports_an_empty_list(): void
    {
        $this->assertSame([], $this->lesson(['doc_links' => null])->documentationLinks());
        $this->assertSame([], $this->lesson(['doc_links' => []])->documentationLinks());
    }

    /** The trainee sees the summary in the outline before opening anything. */
    public function test_the_course_outline_carries_the_lesson_summary(): void
    {
        $lesson = $this->lesson(['summary' => 'Attach a fuel sensor and read its calibration.']);

        $user = $this->trainee();
        app(EnrollEmployee::class)->handle($user, $lesson->course);

        $this->actingAs($user)
            ->get(route('courses.show', $lesson->course->slug))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('modules.0.lessons.0.summary', 'Attach a fuel sensor and read its calibration.'));
    }

    /** All three fields are editable by a trainer, not just by a migration. */
    public function test_the_fields_are_authorable_in_the_admin_panel(): void
    {
        $lesson = $this->lesson([
            'summary' => 'Editable.',
            'doc_links' => [['title' => 'Objects', 'url' => 'https://docs.pilot-gps.com/objects.html']],
        ]);

        $this->actingAs($this->trainer())
            ->get(LessonResource::getUrl('edit', ['record' => $lesson]))
            ->assertSuccessful()
            ->assertSee('Documentation links')
            ->assertSee('Short summary');
    }
}
