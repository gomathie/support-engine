<?php

namespace App\Filament\Widgets;

use App\Enums\CourseStatus;
use App\Enums\ProgressStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\QuizAttempt;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The headline numbers from §10 of the brief.
 *
 * Every figure is scoped to what the signed-in person is allowed to see: an
 * administrator gets the whole company, a manager gets the departments they
 * run. The scoping lives in one place — visibleUserIds() — so a new stat cannot
 * accidentally leak across departments.
 */
class TrainingOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $userIds = $this->visibleUserIds();

        $employees = User::query()->whereIn('id', $userIds);
        $totalEmployees = (clone $employees)->count();
        $activeEmployees = (clone $employees)->where('is_active', true)->count();

        $progress = CourseProgress::query()->whereIn('user_id', $userIds);

        $totalAssignments = (clone $progress)->count();
        $completed = (clone $progress)->where('status', ProgressStatus::Completed->value)->count();
        $inProgress = (clone $progress)->where('status', ProgressStatus::InProgress->value)->count();
        $overdue = (clone $progress)->where('status', ProgressStatus::Overdue->value)->count();

        $completionRate = $totalAssignments > 0
            ? round($completed / $totalAssignments * 100)
            : 0;

        $averageScore = QuizAttempt::query()
            ->whereIn('user_id', $userIds)
            ->completed()
            ->avg('score');

        return [
            Stat::make('Employees', $totalEmployees)
                ->description($activeEmployees.' active')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Published courses', Course::query()
                ->where('status', CourseStatus::Published->value)
                ->count())
                ->description(Course::query()->count().' in total')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('gray'),

            Stat::make('Completion rate', $completionRate.'%')
                ->description($completed.' of '.$totalAssignments.' assignments')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color(match (true) {
                    $completionRate >= 75 => 'success',
                    $completionRate >= 40 => 'warning',
                    default => 'danger',
                }),

            Stat::make('In progress', $inProgress)
                ->description('started but not finished')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Average quiz score', $averageScore !== null ? round($averageScore).'%' : '—')
                ->description(QuizAttempt::query()->whereIn('user_id', $userIds)->completed()->count().' attempts')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($averageScore !== null && $averageScore >= 70 ? 'success' : 'warning'),

            Stat::make('Overdue', $overdue)
                ->description($overdue === 0 ? 'nothing past its deadline' : 'past the deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success'),

            Stat::make('Certificates issued', Certificate::query()
                ->whereIn('user_id', $userIds)
                ->count())
                ->descriptionIcon('heroicon-m-document-check')
                ->color('gray'),
        ];
    }

    /** @return array<int, int> */
    private function visibleUserIds(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->isAdmin()) {
            return User::query()->pluck('id')->all();
        }

        return User::query()
            ->whereIn('department_id', $user->visibleDepartmentIds())
            ->pluck('id')
            ->all();
    }
}
