<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Department;
use App\Models\Lesson;
use App\Models\LessonResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ─── ADMIN PANEL ─────────────────────────────────────────

    public function test_an_employee_cannot_reach_the_admin_panel(): void
    {
        $this->actingAs($this->employee())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_an_admin_can_reach_the_admin_panel(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_a_manager_can_reach_the_admin_panel(): void
    {
        $this->actingAs($this->manager(Department::factory()->create()))
            ->get('/admin')
            ->assertSuccessful();
    }

    // ─── COURSE ACCESS ───────────────────────────────────────

    public function test_an_employee_cannot_open_a_course_they_are_not_enrolled_in(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->employee())
            ->get(route('courses.show', $course->slug))
            ->assertForbidden();
    }

    public function test_an_employee_can_open_a_course_they_are_enrolled_in(): void
    {
        $course = Course::factory()->create();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->get(route('courses.show', $course->slug))
            ->assertOk();
    }

    /**
     * A guessed slug must not expose unfinished material, even to somebody the
     * course has been assigned to.
     */
    public function test_a_draft_course_is_invisible_even_when_enrolled(): void
    {
        $course = Course::factory()->draft()->create();
        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->get(route('courses.show', $course->slug))
            ->assertForbidden();
    }

    public function test_lesson_access_derives_from_course_access(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module, 'module')->create();

        $outsider = $this->employee();

        $this->actingAs($outsider)
            ->get(route('lessons.show', [$course->slug, $lesson->slug]))
            ->assertForbidden();
    }

    public function test_an_unpublished_lesson_cannot_be_opened(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module, 'module')->unpublished()->create();

        $user = $this->employee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->get(route('lessons.show', [$course->slug, $lesson->slug]))
            ->assertForbidden();
    }

    // ─── PRIVATE FILES ───────────────────────────────────────

    /**
     * Internal documents live on a disk with no public URL; this controller is
     * the only path to them, so the policy on it is the whole control.
     */
    public function test_a_lesson_resource_cannot_be_downloaded_without_course_access(): void
    {
        Storage::fake('private');

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module, 'module')->create();

        $path = UploadedFile::fake()
            ->create('internal-policy.pdf', 100, 'application/pdf')
            ->store('lesson-resources', 'private');

        $resource = LessonResource::query()->create([
            'lesson_id' => $lesson->id,
            'name' => 'Internal policy',
            'disk' => 'private',
            'path' => $path,
            'original_filename' => 'internal-policy.pdf',
            'mime_type' => 'application/pdf',
            'is_downloadable' => true,
        ]);

        $this->actingAs($this->employee())
            ->get(route('resources.download', $resource))
            ->assertForbidden();
    }

    public function test_an_enrolled_employee_can_download_a_lesson_resource(): void
    {
        Storage::fake('private');

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module, 'module')->create();

        $path = UploadedFile::fake()
            ->create('handbook.pdf', 100, 'application/pdf')
            ->store('lesson-resources', 'private');

        $resource = LessonResource::query()->create([
            'lesson_id' => $lesson->id,
            'name' => 'Handbook',
            'disk' => 'private',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'is_downloadable' => true,
        ]);

        $user = $this->employee();
        app(EnrollEmployee::class)->handle($user, $course);

        $this->actingAs($user)
            ->get(route('resources.download', $resource))
            ->assertOk();
    }

    // ─── EMPLOYEE DATA PRIVACY ───────────────────────────────

    public function test_a_manager_sees_only_their_own_departments(): void
    {
        $mine = Department::factory()->create();
        $theirs = Department::factory()->create();

        $manager = $this->manager($mine);

        $inside = $this->employee($mine);
        $outside = $this->employee($theirs);

        $this->assertTrue($manager->can('viewProgress', $inside));
        $this->assertFalse($manager->can('viewProgress', $outside));
    }

    public function test_an_employee_cannot_read_another_employees_record(): void
    {
        $department = Department::factory()->create();

        $one = $this->employee($department);
        $two = $this->employee($department);

        $this->assertFalse($one->can('viewProgress', $two));
        $this->assertTrue($one->can('viewProgress', $one));
    }

    public function test_an_admin_sees_everybody(): void
    {
        $admin = $this->admin();
        $employee = $this->employee(Department::factory()->create());

        $this->assertTrue($admin->can('viewProgress', $employee));
    }

    // ─── SELF-ENROLLMENT ─────────────────────────────────────

    public function test_required_courses_cannot_be_self_enrolled(): void
    {
        $course = Course::factory()->required()->create();

        $this->actingAs($this->employee())
            ->post(route('courses.enroll', $course->slug))
            ->assertForbidden();
    }

    public function test_optional_courses_can_be_self_enrolled(): void
    {
        $course = Course::factory()->create();
        $user = $this->employee();

        $this->actingAs($user)
            ->post(route('courses.enroll', $course->slug))
            ->assertRedirect();

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'source' => 'self',
        ]);
    }
}
