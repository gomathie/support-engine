<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Department;
use App\Models\DiagnosticTree;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Breadth rather than depth: every employee-facing page is opened once and
 * checked for a 200 and the right Inertia component.
 *
 * These catch the whole class of mistakes the unit tests miss — a renamed prop,
 * a missing route, a controller referencing a relationship that no longer
 * exists — none of which show up until a page is actually rendered.
 */
class PageRendersTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_employee_page_renders(): void
    {
        $department = Department::factory()->create();
        $user = $this->employee($department);

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module, 'module')->create();

        $quiz = Quiz::factory()->create(['course_id' => $course->id]);
        QuizQuestion::factory()->for($quiz)->withOptions(4, [0])->create();

        DiagnosticTree::factory()->create();

        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user);

        $pages = [
            ['Dashboard', route('dashboard')],
            ['Courses/Index', route('courses.index')],
            ['Courses/Show', route('courses.show', $course->slug)],
            ['Lessons/Show', route('lessons.show', [$course->slug, $lesson->slug])],
            ['Quizzes/Show', route('quizzes.show', [$course->slug, $quiz->id])],
            ['Progress/Index', route('progress.index')],
            ['Certificates/Index', route('certificates.index')],
            ['SupportPanel/Index', route('support-panel.index')],
            ['Profile/Edit', route('profile.edit')],
        ];

        foreach ($pages as [$component, $url]) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(
                    fn (AssertableInertia $page) => $page->component($component)
                );
        }
    }

    public function test_the_course_filters_do_not_break_the_listing(): void
    {
        $user = $this->employee();
        $course = Course::factory()->create(['category' => 'TRACK 1']);

        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user);

        // ILIKE is PostgreSQL-specific; this is the test that would have caught
        // it had the suite been left on SQLite.
        $this->get(route('courses.index', ['search' => 'track']))->assertOk();
        $this->get(route('courses.index', ['category' => 'TRACK 1']))->assertOk();
        $this->get(route('courses.index', ['status' => 'not_started']))->assertOk();
    }

    public function test_taking_a_quiz_renders_the_questions(): void
    {
        $user = $this->employee();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id]);

        QuizQuestion::factory()->count(3)->for($quiz)->withOptions(4, [0])->create();

        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->post(route('quizzes.start', [$course->slug, $quiz->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Quizzes/Take')
                ->has('questions', 3)
                ->has('questions.0.options', 4)
                // The options sent to the browser carry an id and a label only.
                ->has('questions.0.options.0', fn (AssertableInertia $option) => $option
                    ->has('id')
                    ->has('label')
                    ->etc()
                )
            );
    }

    public function test_the_admin_panel_dashboard_renders(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertSuccessful();
    }
}
