<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteLesson;
use App\Filament\Pages\Reports;
use App\Filament\Resources\AssignmentRules\AssignmentRuleResource;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\CourseEnrollments\CourseEnrollmentResource;
use App\Filament\Resources\CourseModules\CourseModuleResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\DiagnosticTrees\DiagnosticTreeResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AssignmentRule;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Department;
use App\Models\DiagnosticTree;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seeds one of everything, so each admin screen renders against real rows
     * rather than an empty table — an empty table hides half the bugs.
     */
    private function seedContent(): array
    {
        $department = Department::factory()->create();

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module, 'module')->create();

        $quiz = Quiz::factory()->create(['course_id' => $course->id]);
        QuizQuestion::factory()->for($quiz)->withOptions(4, [0])->create();

        DiagnosticTree::factory()->withSteps(3)->create();

        AssignmentRule::query()->create([
            'course_id' => $course->id,
            'target_type' => AssignmentRule::TARGET_DEPARTMENT,
            'target_id' => $department->id,
            'is_active' => true,
        ]);

        $employee = $this->employee($department);
        app(EnrollEmployee::class)->handle($employee, $course);

        return compact('department', 'course', 'module', 'lesson', 'quiz', 'employee');
    }

    public function test_every_admin_list_screen_renders_for_an_admin(): void
    {
        $this->seedContent();

        $this->actingAs($this->admin());

        $pages = [
            CourseResource::getUrl('index'),
            CourseModuleResource::getUrl('index'),
            LessonResource::getUrl('index'),
            QuizResource::getUrl('index'),
            UserResource::getUrl('index'),
            DepartmentResource::getUrl('index'),
            CourseEnrollmentResource::getUrl('index'),
            AssignmentRuleResource::getUrl('index'),
            CertificateResource::getUrl('index'),
            DiagnosticTreeResource::getUrl('index'),
            Reports::getUrl(),
        ];

        foreach ($pages as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_every_admin_create_and_edit_screen_renders(): void
    {
        ['course' => $course, 'module' => $module, 'lesson' => $lesson, 'quiz' => $quiz] = $this->seedContent();

        $this->actingAs($this->admin());

        $pages = [
            CourseResource::getUrl('create'),
            CourseResource::getUrl('edit', ['record' => $course]),
            CourseModuleResource::getUrl('edit', ['record' => $module]),
            LessonResource::getUrl('edit', ['record' => $lesson]),
            QuizResource::getUrl('edit', ['record' => $quiz]),
            UserResource::getUrl('create'),
        ];

        foreach ($pages as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_the_dashboard_widgets_render(): void
    {
        Bus::fake();

        ['course' => $course, 'employee' => $employee] = $this->seedContent();

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($employee, $lesson);
        }

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertSuccessful();
    }

    // ─── MANAGER SCOPING ─────────────────────────────────────

    /**
     * The list, not just the record. A manager must not be able to learn who
     * works in another department by looking at the employee table.
     */
    public function test_a_manager_only_sees_employees_in_their_own_departments(): void
    {
        $mine = Department::factory()->create(['name' => 'Technical Support']);
        $theirs = Department::factory()->create(['name' => 'Operations']);

        $manager = $this->manager($mine);

        $visible = $this->employee($mine, ['name' => 'Visible Employee']);
        $hidden = $this->employee($theirs, ['name' => 'Hidden Employee']);

        $this->actingAs($manager)
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Visible Employee')
            ->assertDontSee('Hidden Employee');
    }

    public function test_a_manager_only_sees_their_departments_in_the_report(): void
    {
        $mine = Department::factory()->create();
        $theirs = Department::factory()->create();

        $manager = $this->manager($mine);

        $course = Course::factory()->create(['title' => 'Shared Course']);

        $visible = $this->employee($mine, ['name' => 'Visible Employee']);
        $hidden = $this->employee($theirs, ['name' => 'Hidden Employee']);

        app(EnrollEmployee::class)->handle($visible, $course);
        app(EnrollEmployee::class)->handle($hidden, $course);

        $this->actingAs($manager)
            ->get(Reports::getUrl())
            ->assertSuccessful()
            ->assertSee('Visible Employee')
            ->assertDontSee('Hidden Employee');
    }

    public function test_an_employee_cannot_reach_any_admin_screen(): void
    {
        $this->seedContent();

        $this->actingAs($this->employee());

        foreach ([UserResource::getUrl('index'), CourseResource::getUrl('index'), Reports::getUrl()] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    /**
     * Certificates are issued by the system, never typed in — a hand-written
     * one would be indistinguishable from an earned one.
     */
    public function test_certificates_cannot_be_created_by_hand(): void
    {
        $this->assertFalse(CertificateResource::canCreate());

        $this->actingAs($this->admin())
            ->get(CertificateResource::getUrl('index'))
            ->assertSuccessful();
    }
}
