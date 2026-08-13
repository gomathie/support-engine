<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued: assigning a course to a whole department sends one of these per
 * person, and none of that belongs in the request that clicked the button.
 */
class CourseAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Course $course,
        public readonly ?CourseEnrollment $enrollment = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Training assigned: '.$this->course->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been assigned **'.$this->course->title.'**.');

        if ($this->course->summary) {
            $mail->line($this->course->summary);
        }

        if ($this->enrollment?->due_at) {
            $mail->line('It is due by **'.$this->enrollment->due_at->format('j F Y').'**.');
        }

        return $mail
            ->action('Start the course', route('courses.show', $this->course->slug))
            ->line('You can see everything assigned to you on your dashboard.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_assigned',
            'title' => 'New training assigned',
            'body' => $this->course->title,
            'course_slug' => $this->course->slug,
            'due_at' => $this->enrollment?->due_at?->toIso8601String(),
            'url' => route('courses.show', $this->course->slug),
        ];
    }
}
