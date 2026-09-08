<?php

namespace App\Filament\Resources\Modules\Tables;

use App\Models\Course;
use App\Models\Module;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label('Course')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('subtitle')
                    ->searchable()
                    ->wrap()
                    ->limit(60),

                TextColumn::make('lessons_count')
                    ->label('Lessons')
                    ->counts('lessons')
                    ->alignEnd(),

                /*
                 * Every lesson is meant to end with a knowledge check, and
                 * passing it is required to finish the course. A lesson without
                 * one can be read and left — so the gap is shown here rather
                 * than discovered when somebody completes a course having been
                 * tested on nothing.
                 */
                TextColumn::make('knowledge_check')
                    ->label('Knowledge check')
                    ->state(fn (Module $record) => $record->hasKnowledgeCheck() ? 'Yes' : 'Missing')
                    ->badge()
                    ->color(fn ($state) => $state === 'Yes' ? 'success' : 'warning')
                    ->tooltip(fn (Module $record) => $record->hasKnowledgeCheck()
                        ? null
                        : 'No published quiz at the end of this lesson. Trainees can read it and move on.'),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                TextColumn::make('position')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all()),
            ])
            // Drag to reorder, writing straight to the position column. The
            // brief asks for reordering; this is the least fiddly way to give
            // it without a bespoke screen.
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
