<?php

namespace App\Filament\Resources\AssignmentRules\Tables;

use App\Actions\Enrollment\SyncAssignmentRules;
use App\Models\AssignmentRule;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AssignmentRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('target')
                    ->label('Assigned to')
                    ->state(fn (AssignmentRule $record) => $record->describeTarget())
                    ->badge()
                    ->color('info'),

                // Evaluated live, so it reflects who the rule matches right now
                // rather than who it matched when it was written.
                TextColumn::make('matches')
                    ->label('Matches')
                    ->state(fn (AssignmentRule $record) => $record->matchingUsers()->count())
                    ->alignEnd(),

                TextColumn::make('enrollments_count')
                    ->label('Enrolled')
                    ->counts('enrollments')
                    ->alignEnd(),

                TextColumn::make('due_days')
                    ->label('Due in')
                    ->formatStateUsing(fn ($state) => $state ? $state.' days' : 'course default')
                    ->alignEnd(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all()),

                SelectFilter::make('target_type')->options([
                    AssignmentRule::TARGET_DEPARTMENT => 'Department',
                    AssignmentRule::TARGET_ROLE => 'Role',
                    AssignmentRule::TARGET_USER => 'Employee',
                    AssignmentRule::TARGET_ALL => 'Everyone',
                ]),

                TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Action::make('syncAll')
                    ->label('Run all rules now')
                    ->icon('heroicon-o-play')
                    ->requiresConfirmation()
                    ->modalDescription('Enrolls everyone that every active rule currently matches. Existing enrollments are left alone.')
                    ->action(function (SyncAssignmentRules $sync): void {
                        $created = $sync->all();

                        Notification::make()
                            ->success()
                            ->title($created === 0 ? 'Everything already up to date' : $created.' new enrollments created')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('run')
                    ->label('Run')
                    ->icon('heroicon-o-play')
                    ->color('gray')
                    ->action(function (AssignmentRule $record, SyncAssignmentRules $sync): void {
                        $created = $sync->forRule($record);

                        Notification::make()
                            ->success()
                            ->title($created === 0 ? 'Nobody new to enroll' : $created.' enrolled')
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
