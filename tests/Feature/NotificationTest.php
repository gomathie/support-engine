<?php

namespace Tests\Feature;

use App\Actions\Enrollment\EnrollEmployee;
use App\Actions\Progress\CompleteLesson;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Notifications\CourseAssigned;
use App\Notifications\TrainingDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_being_assigned_a_course_sends_a_notification(): void
    {
        Notification::fake();

        $user = $this->employee();
        $course = Course::factory()->create();

        app(EnrollEmployee::class)->handle($user, $course);

        Notification::assertSentTo(
            $user,
            CourseAssigned::class,
            fn (CourseAssigned $n) => $n->course->is($course),
        );
    }

    /**
     * Re-running an assignment rule must not re-notify people who already have
     * the course — otherwise the hourly sync becomes a mail loop.
     */
    public function test_re_enrolling_does_not_notify_again(): void
    {
        Notification::fake();

        $user = $this->employee();
        $course = Course::factory()->create();

        app(EnrollEmployee::class)->handle($user, $course);
        app(EnrollEmployee::class)->handle($user, $course);
        app(EnrollEmployee::class)->handle($user, $course);

        Notification::assertSentToTimes($user, CourseAssigned::class, 1);
    }

    public function test_the_reminder_command_notifies_overdue_and_due_soon(): void
    {
        Notification::fake();

        $course = Course::factory()->create();

        $overdueUser = $this->employee();
        $dueSoonUser = $this->employee();
        $notDueUser = $this->employee();

        app(EnrollEmployee::class)->handle($overdueUser, $course)
            ->update(['due_at' => now()->subDays(3)]);

        app(EnrollEmployee::class)->handle($dueSoonUser, $course)
            ->update(['due_at' => now()->addDays(2)]);

        app(EnrollEmployee::class)->handle($notDueUser, $course)
            ->update(['due_at' => now()->addDays(90)]);

        $this->artisan('training:send-reminders')->assertSuccessful();

        Notification::assertSentTo($overdueUser, TrainingDue::class, fn ($n) => $n->isOverdue);
        Notification::assertSentTo($dueSoonUser, TrainingDue::class, fn ($n) => ! $n->isOverdue);
        Notification::assertNotSentTo($notDueUser, TrainingDue::class);
    }

    /** Somebody who has finished is not chased, however late they were. */
    public function test_completed_courses_are_not_chased(): void
    {
        Notification::fake();

        $course = Course::factory()->create();
        $module = CourseModule::factory()->for($course)->create();
        Lesson::factory()->count(2)->for($module, 'module')->create();

        $user = $this->employee();

        app(EnrollEmployee::class)->handle($user, $course->fresh())
            ->update(['due_at' => now()->subDays(10)]);

        foreach ($course->lessons as $lesson) {
            app(CompleteLesson::class)->handle($user, $lesson);
        }

        $this->artisan('training:send-reminders')->assertSuccessful();

        Notification::assertNotSentTo($user, TrainingDue::class);
    }

    public function test_an_employee_can_mark_a_notification_read(): void
    {
        $user = $this->employee();
        $course = Course::factory()->create();

        app(EnrollEmployee::class)->handle($user, $course);

        $this->assertSame(1, $user->unreadNotifications()->count());

        $id = $user->unreadNotifications()->first()->id;

        $this->actingAs($user)
            ->post(route('notifications.read', $id))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    /** A guessed id belonging to somebody else must do nothing. */
    public function test_an_employee_cannot_mark_another_persons_notification_read(): void
    {
        $owner = $this->employee();
        $intruder = $this->employee();

        app(EnrollEmployee::class)->handle($owner, Course::factory()->create());

        $id = $owner->unreadNotifications()->first()->id;

        $this->actingAs($intruder)
            ->post(route('notifications.read', $id))
            ->assertRedirect();

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());
    }
}
