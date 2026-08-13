<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Enums\CourseStatus;
use App\Models\Course;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Course $record) => $record->summary),

                TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (CourseStatus $state) => $state->label())
                    ->color(fn (CourseStatus $state) => match ($state) {
                        CourseStatus::Published => 'success',
                        CourseStatus::Draft => 'gray',
                        CourseStatus::Archived => 'warning',
                    })
                    ->sortable(),

                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('modules_count')
                    ->label('Modules')
                    ->counts('modules')
                    ->alignEnd(),

                TextColumn::make('lessons_count')
                    ->label('Lessons')
                    ->counts('lessons')
                    ->alignEnd(),

                TextColumn::make('enrollments_count')
                    ->label('Enrolled')
                    ->counts('enrollments')
                    ->alignEnd(),

                // Completion rate per course — one of the headline numbers the
                // brief asks the admin dashboard to surface.
                TextColumn::make('completion_rate')
                    ->label('Completed')
                    ->state(function (Course $record): string {
                        $total = $record->progress()->count();

                        if ($total === 0) {
                            return '—';
                        }

                        $done = $record->progress()->completed()->count();

                        return round($done / $total * 100).'%';
                    })
                    ->alignEnd(),

                TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(CourseStatus::options()),
                SelectFilter::make('category')
                    ->options(fn () => Course::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->all()),
                TernaryFilter::make('is_required')->label('Required training'),
            ])
            ->recordActions([
                // Publishing is a distinct decision from editing, so it gets its
                // own confirmed action rather than being a dropdown buried in
                // the form.
                Action::make('publish')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Employees will be able to open this course immediately.')
                    ->visible(fn (Course $record) => $record->status !== CourseStatus::Published)
                    ->action(fn (Course $record) => $record->update([
                        'status' => CourseStatus::Published,
                        'published_at' => $record->published_at ?? now(),
                    ])),

                Action::make('unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(
                        'The course stays visible to anyone already part-way through it, '
                        .'but is not offered to anyone new.'
                    )
                    ->visible(fn (Course $record) => $record->status === CourseStatus::Published)
                    ->action(fn (Course $record) => $record->update(['status' => CourseStatus::Archived])),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('title');
    }
}
