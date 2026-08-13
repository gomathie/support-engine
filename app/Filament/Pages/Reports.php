<?php

namespace App\Filament\Pages;

use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Department;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * One report that answers the questions in §10 of the brief, rather than a
 * dozen fixed ones: every row is one employee on one course, and the filters
 * cut it by employee, department, course, status and date.
 *
 * It reads the course_progress rollup rather than recomputing percentages, so
 * it stays fast as the number of enrollments grows.
 */
class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Training report';

    protected string $view = 'filament.pages.reports';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('user.department.name')
                    ->label('Department')
                    ->badge()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => round((float) $state).'%')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('completed_lessons')
                    ->label('Lessons')
                    ->state(fn (CourseProgress $r) => $r->completed_lessons.' / '.$r->total_lessons)
                    ->alignEnd(),

                TextColumn::make('final_score')
                    ->label('Quiz')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : round((float) $state).'%')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ProgressStatus $state) => $state->label())
                    ->color(fn (ProgressStatus $state) => match ($state) {
                        ProgressStatus::Completed => 'success',
                        ProgressStatus::InProgress => 'info',
                        ProgressStatus::Failed, ProgressStatus::Overdue => 'danger',
                        ProgressStatus::NotStarted => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date('j M Y')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('last_activity_at')
                    ->label('Last activity')
                    ->since()
                    ->sortable()
                    ->placeholder('never')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->label('Department')
                    ->options(fn () => $this->visibleDepartments())
                    ->query(fn (Builder $q, array $data) => $q->when(
                        $data['value'] ?? null,
                        fn ($q, $id) => $q->whereHas('user', fn ($u) => $u->where('department_id', $id)),
                    )),

                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                SelectFilter::make('status')
                    ->options(ProgressStatus::options())
                    ->multiple(),

                Filter::make('completed_between')
                    ->schema([
                        DatePicker::make('from')->label('Completed from'),
                        DatePicker::make('until')->label('Completed until'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('completed_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('completed_at', '<=', $d))),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    // Exports exactly what is on screen, filters and all —
                    // an export that ignores the filters is a trap.
                    ->action(fn () => $this->exportCsv()),
            ])
            ->defaultSort('user.name')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Nothing to report yet')
            ->emptyStateDescription('Assign a course to somebody and their progress will appear here.');
    }

    /**
     * Managers see their own departments and nobody else's. Applied to the
     * query, not just the filter options, so it cannot be bypassed by editing
     * the URL.
     */
    private function baseQuery(): Builder
    {
        $user = auth()->user();

        return CourseProgress::query()
            ->with(['user.department', 'course'])
            ->when(
                $user && ! $user->isAdmin(),
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($u) => $u->whereIn('department_id', $user->visibleDepartmentIds()),
                ),
            );
    }

    /** @return array<int, string> */
    private function visibleDepartments(): array
    {
        $user = auth()->user();

        return Department::query()
            ->when(
                $user && ! $user->isAdmin(),
                fn ($q) => $q->whereIn('id', $user->visibleDepartmentIds()),
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function exportCsv(): StreamedResponse
    {
        $rows = $this->getFilteredSortedTableQuery()->get();

        $filename = 'training-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee', 'Email', 'Department', 'Course', 'Progress %',
                'Lessons completed', 'Lessons total', 'Quiz score %', 'Status',
                'Started', 'Completed',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->user?->name,
                    $row->user?->email,
                    $row->user?->department?->name,
                    $row->course?->title,
                    round((float) $row->percentage),
                    $row->completed_lessons,
                    $row->total_lessons,
                    $row->final_score === null ? '' : round((float) $row->final_score),
                    $row->status->label(),
                    $row->started_at?->format('Y-m-d'),
                    $row->completed_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', \App\Models\User::class) ?? false;
    }
}
