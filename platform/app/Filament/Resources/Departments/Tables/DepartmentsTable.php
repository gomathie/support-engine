<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Enums\ProgressStatus;
use App\Models\CourseProgress;
use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Department $record) => $record->description),

                TextColumn::make('members_count')
                    ->label('Employees')
                    ->counts('members')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('managers.name')
                    ->label('Managers')
                    ->badge()
                    ->placeholder('none'),

                // Department completion rate — the headline reporting number,
                // computed in one aggregate rather than per row.
                TextColumn::make('completion')
                    ->label('Completion')
                    ->state(function (Department $record): string {
                        $rows = CourseProgress::query()
                            ->whereHas('user', fn ($q) => $q->where('department_id', $record->id))
                            ->get(['status']);

                        if ($rows->isEmpty()) {
                            return '—';
                        }

                        $done = $rows->where('status', ProgressStatus::Completed)->count();

                        return round($done / $rows->count() * 100).'%';
                    })
                    ->alignEnd(),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->state(fn (Department $record) => CourseProgress::query()
                        ->where('status', ProgressStatus::Overdue->value)
                        ->whereHas('user', fn ($q) => $q->where('department_id', $record->id))
                        ->count())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->alignEnd(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
