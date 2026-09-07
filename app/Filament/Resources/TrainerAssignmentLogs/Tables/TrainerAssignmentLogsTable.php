<?php

namespace App\Filament\Resources\TrainerAssignmentLogs\Tables;

use App\Enums\Role;
use App\Models\TrainerAssignmentLog;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainerAssignmentLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('When')
                    ->dateTime('j M Y, H:i')
                    ->sortable()
                    ->description(fn (TrainerAssignmentLog $record) => $record->occurred_at?->diffForHumans()),

                TextColumn::make('trainee.name')
                    ->label('Trainee')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn (TrainerAssignmentLog $record) => $record->actionLabel())
                    ->color(fn (string $state) => match ($state) {
                        TrainerAssignmentLog::ACTION_ASSIGNED => 'success',
                        TrainerAssignmentLog::ACTION_REASSIGNED => 'warning',
                        TrainerAssignmentLog::ACTION_UNASSIGNED => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('transition')
                    ->label('Change')
                    ->state(fn (TrainerAssignmentLog $record) => $record->transition())
                    ->wrap(),

                /*
                 * How much unmarked work moved with them. Recorded at the moment
                 * of the change because it cannot be reconstructed afterwards —
                 * and it is the number that answers "was that handover fair".
                 */
                TextColumn::make('pending_grading_inherited')
                    ->label('Work inherited')
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'gray')
                    ->alignEnd()
                    ->tooltip('Written answers and practical submissions still unmarked when the trainee moved.'),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->wrap()
                    ->placeholder('none given')
                    ->toggleable(),

                TextColumn::make('actor.name')
                    ->label('By')
                    ->placeholder('system')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        TrainerAssignmentLog::ACTION_ASSIGNED => 'Assigned',
                        TrainerAssignmentLog::ACTION_REASSIGNED => 'Reassigned',
                        TrainerAssignmentLog::ACTION_UNASSIGNED => 'Unassigned',
                    ]),

                SelectFilter::make('trainee_id')
                    ->label('Trainee')
                    ->options(fn () => User::query()
                        ->role(Role::Trainee->value)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                // "Everything that ever moved to or from this trainer" — the
                // question asked when a handover is being reviewed.
                SelectFilter::make('trainer')
                    ->label('Trainer (either side)')
                    ->options(fn () => User::query()
                        ->role(Role::Trainer->value)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $id) => $q->where(fn ($w) => $w
                            ->where('from_trainer_id', $id)
                            ->orWhere('to_trainer_id', $id)),
                    )),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->emptyStateHeading('No assignment changes yet')
            ->emptyStateDescription('Every change of trainer is recorded here, with who made it and why.');
    }
}
