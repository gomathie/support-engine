<?php

namespace App\Filament\Resources\PracticalTasks\Tables;

use App\Enums\SubmissionStatus;
use App\Models\Course;
use App\Models\PracticalTask;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PracticalTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->description(fn (PracticalTask $record) => $record->topic?->title),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->badge()
                    ->sortable(),

                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->alignEnd(),

                // What the trainer queue actually looks like for this task.
                TextColumn::make('awaiting')
                    ->label('To mark')
                    ->state(fn (PracticalTask $record) => $record->submissions()
                        ->where('status', SubmissionStatus::Submitted->value)
                        ->count() ?: '—')
                    ->badge()
                    ->color(fn ($state) => $state === '—' ? 'gray' : 'warning')
                    ->alignEnd(),

                TextColumn::make('pass_rate')
                    ->label('Pass rate')
                    ->state(function (PracticalTask $record): string {
                        $marked = $record->submissions()->whereNotNull('passed')->count();

                        if ($marked === 0) {
                            return '—';
                        }

                        $passed = $record->submissions()->where('passed', true)->count();

                        return round($passed / $marked * 100).'%';
                    })
                    ->alignEnd(),

                IconColumn::make('requires_second_marker')
                    ->label('Double-marked')
                    ->boolean()
                    ->tooltip('Two trainers mark independently and must agree before it settles.'),

                IconColumn::make('is_published')
                    ->label('Live')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                TernaryFilter::make('is_published')->label('Published'),
                TernaryFilter::make('requires_second_marker')->label('Double-marked'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position');
    }
}
