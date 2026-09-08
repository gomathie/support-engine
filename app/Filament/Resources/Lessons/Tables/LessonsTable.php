<?php

namespace App\Filament\Resources\Lessons\Tables;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Lesson;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap()
                    ->description(fn (Lesson $record) => $record->module?->title),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (LessonType $state) => $state->label())
                    ->color('gray'),

                TextColumn::make('completion_requirement')
                    ->label('Completed by')
                    ->formatStateUsing(fn (CompletionRequirement $state) => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('resources_count')
                    ->label('Files')
                    ->counts('resources')
                    ->alignEnd(),

                TextColumn::make('progress_count')
                    ->label('Completions')
                    ->counts(['progress' => fn ($q) => $q->whereNotNull('completed_at')])
                    ->alignEnd()
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                SelectFilter::make('type')->options(LessonType::options()),

                TernaryFilter::make('is_published'),
            ])
            // Position is scoped to the module, so reordering only makes sense
            // once the list is filtered down to one course.
            ->reorderable('position')
            ->defaultSort('position')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
