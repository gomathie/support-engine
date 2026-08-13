<?php

namespace App\Filament\Widgets;

use App\Models\CourseEnrollment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The list an administrator or manager actually acts on: who is past their
 * deadline and has not finished.
 */
class OverdueTraining extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Overdue training';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (CourseEnrollment $record) => $record->user?->department?->name),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->wrap(),

                TextColumn::make('due_at')
                    ->label('Was due')
                    ->date('j M Y')
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('overdue_by')
                    ->label('Overdue by')
                    ->state(fn (CourseEnrollment $record) => $record->due_at
                        ? $record->due_at->diffForHumans(null, true)
                        : '—')
                    ->color('danger'),
            ])
            ->defaultSort('due_at')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nothing overdue')
            ->emptyStateDescription('Every assigned course is either finished or still within its deadline.');
    }

    private function query(): Builder
    {
        $user = auth()->user();

        return CourseEnrollment::query()
            ->with(['user.department', 'course'])
            ->overdue()

            // Exclude anyone who has actually finished — a completed course is
            // not overdue however late it was.
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw(1)
                    ->from('course_progress')
                    ->whereColumn('course_progress.user_id', 'course_enrollments.user_id')
                    ->whereColumn('course_progress.course_id', 'course_enrollments.course_id')
                    ->where('course_progress.status', 'completed');
            })

            ->when(
                $user && ! $user->isAdmin(),
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($u) => $u->whereIn('department_id', $user->visibleDepartmentIds()),
                ),
            );
    }
}
