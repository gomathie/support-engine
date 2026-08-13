<?php

namespace App\Notifications;

use App\Models\CourseEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CourseEnrollment $enrollment,
        public readonly bool $isOverdue,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->enrollment->course;
        $due = $this->enrollment->due_at;

        $mail = (new MailMessage)
            ->subject($this->isOverdue
                ? 'Overdue training: '.$course->title
                : 'Training due soon: '.$course->title)
            ->greeting('Hello '.$notifiable->name.',');

        $mail->line($this->isOverdue
            ? '**'.$course->title.'** was due on '.$due->format('j F Y').' and is not yet complete.'
            : '**'.$course->title.'** is due on '.$due->format('j F Y').'.');

        return $mail
            ->action('Continue the course', route('courses.show', $course->slug))
            ->line('If you have already finished it, no action is needed.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->isOverdue ? 'training_overdue' : 'training_due_soon',
            'title' => $this->isOverdue ? 'Training overdue' : 'Training due soon',
            'body' => $this->enrollment->course->title,
            'course_slug' => $this->enrollment->course->slug,
            'due_at' => $this->enrollment->due_at?->toIso8601String(),
            'url' => route('courses.show', $this->enrollment->course->slug),
        ];
    }
}
