<?php

namespace App\Console\Commands;

use App\Enums\ProgressStatus;
use App\Models\CourseEnrollment;
use App\Notifications\TrainingDue;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendTrainingReminders extends Command
{
    protected $signature = 'training:send-reminders
                            {--days=7 : How many days ahead counts as "due soon"}
                            {--dry-run : List who would be notified without sending}';

    protected $description = 'Notify employees about training that is due soon or already overdue';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $dueSoon = $this->incomplete()
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDays($days)])
            ->get();

        $overdue = $this->incomplete()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get();

        $this->info($dueSoon->count().' due within '.$days.' days, '.$overdue->count().' overdue.');

        foreach ([[$dueSoon, false], [$overdue, true]] as [$set, $isOverdue]) {
            foreach ($set as $enrollment) {
                $line = sprintf(
                    '%s  %-30s %s',
                    $isOverdue ? 'OVERDUE ' : 'DUE SOON',
                    mb_substr($enrollment->user->name, 0, 30),
                    $enrollment->course->title,
                );

                if ($dryRun) {
                    $this->line($line);

                    continue;
                }

                $enrollment->user->notify(new TrainingDue($enrollment, $isOverdue));
            }
        }

        if ($dryRun) {
            $this->comment('Dry run — nothing was sent.');
        }

        return self::SUCCESS;
    }

    /**
     * Enrollments whose course is not finished.
     *
     * Checked against the course_progress rollup rather than counting lessons,
     * so this stays one query however many enrollments exist.
     */
    private function incomplete(): Builder
    {
        return CourseEnrollment::query()
            ->with(['user', 'course'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw(1)
                    ->from('course_progress')
                    ->whereColumn('course_progress.user_id', 'course_enrollments.user_id')
                    ->whereColumn('course_progress.course_id', 'course_enrollments.course_id')
                    ->where('course_progress.status', ProgressStatus::Completed->value);
            });
    }
}
