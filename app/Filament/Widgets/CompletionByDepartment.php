<?php

namespace App\Filament\Widgets;

use App\Enums\ProgressStatus;
use App\Models\CourseProgress;
use App\Models\Department;
use Filament\Widgets\ChartWidget;

class CompletionByDepartment extends ChartWidget
{
    protected ?string $heading = 'Completion by department';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $user = auth()->user();

        $departments = Department::query()
            ->when(
                $user && ! $user->isAdmin(),
                fn ($q) => $q->whereIn('id', $user->visibleDepartmentIds()),
            )
            ->orderBy('name')
            ->get();

        // One grouped aggregate rather than a query per department.
        $counts = CourseProgress::query()
            ->join('users', 'users.id', '=', 'course_progress.user_id')
            ->whereIn('users.department_id', $departments->pluck('id'))
            ->selectRaw('users.department_id, course_progress.status, count(*) as total')
            ->groupBy('users.department_id', 'course_progress.status')
            ->get()
            ->groupBy('department_id');

        $rates = $departments->map(function (Department $department) use ($counts) {
            $rows = $counts->get($department->id, collect());
            $total = $rows->sum('total');

            if ($total === 0) {
                return 0;
            }

            $done = $rows->firstWhere('status', ProgressStatus::Completed->value)?->total ?? 0;

            return round($done / $total * 100);
        });

        return [
            'datasets' => [
                [
                    'label' => 'Completed (%)',
                    'data' => $rates->all(),
                    // #0284c7 — the prototype's primary, so the admin charts
                    // read as the same product as the employee portal.
                    'backgroundColor' => '#0284c7',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $departments->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => ['callback' => null],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
