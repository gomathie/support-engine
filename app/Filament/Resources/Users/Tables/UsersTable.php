<?php

namespace App\Filament\Resources\Users\Tables;

use App\Actions\Enrollment\SyncAssignmentRules;
use App\Enums\ProgressStatus;
use App\Enums\Role;
use App\Models\CompetencyArea;
use App\Models\Department;
use App\Models\Level;
use App\Models\TraineeLevel;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record) => $record->job_title),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('employee_number')
                    ->label('No.')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Role::Admin->value => 'danger',
                        Role::Trainer->value => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('enrollments_count')
                    ->label('Assigned')
                    ->counts('enrollments')
                    ->alignEnd()
                    ->sortable(),

                // Completed / assigned, at a glance — the number a manager
                // actually wants from this screen.
                TextColumn::make('completed')
                    ->label('Completed')
                    ->state(function (User $record): string {
                        $assigned = $record->enrollments()->count();

                        if ($assigned === 0) {
                            return '—';
                        }

                        $done = $record->courseProgress()
                            ->where('status', ProgressStatus::Completed->value)
                            ->count();

                        return $done.' / '.$assigned;
                    })
                    ->alignEnd(),

                // The competency ladder, per area. Read as "Basic · Sensors" —
                // one badge per rung held, because a single overall level would
                // hide the gap a trainer needs to see.
                TextColumn::make('competency')
                    ->label('Competency')
                    ->state(fn (User $record) => $record->competencyLevels()
                        ->active()
                        ->with(['level', 'competencyArea'])
                        ->get()
                        ->sortByDesc(fn (TraineeLevel $award) => $award->level?->position)
                        ->map(fn (TraineeLevel $award) => trim(
                            ($award->level?->name ?? '?').' · '.($award->competencyArea?->name ?? '?')
                        ))
                        ->values()
                        ->all())
                    ->badge()
                    ->color('success')
                    ->placeholder('none yet')
                    ->wrap(),

                TextColumn::make('certificates_count')
                    ->label('Certs')
                    ->counts('certificates')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('last_login_at')
                    ->label('Last seen')
                    ->since()
                    ->placeholder('never')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->all()),

                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple(),

                TernaryFilter::make('is_active')->label('Active'),

                /*
                 * "Who is Level 1 in Sensors" — the question the whole
                 * competency model exists to answer. Two independent selects
                 * rather than one combined list, so "everybody at Basic,
                 * anywhere" and "everybody in Sensors, any rung" both work.
                 */
                Filter::make('competency')
                    ->schema([
                        Select::make('level_id')
                            ->label('Holds level')
                            ->options(fn () => Level::query()->orderBy('position')->pluck('name', 'id')->all())
                            ->placeholder('Any'),

                        Select::make('competency_area_id')
                            ->label('In area')
                            ->options(fn () => CompetencyArea::query()->orderBy('position')->pluck('name', 'id')->all())
                            ->placeholder('Any'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['level_id'] ?? null) && blank($data['competency_area_id'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('competencyLevels', fn (Builder $q) => $q
                            ->whereNull('revoked_at')
                            ->when($data['level_id'] ?? null, fn (Builder $q, $id) => $q->where('level_id', $id))
                            ->when($data['competency_area_id'] ?? null, fn (Builder $q, $id) => $q->where('competency_area_id', $id)));
                    })
                    ->indicateUsing(function (array $data): array {
                        $parts = [];

                        if (filled($data['level_id'] ?? null)) {
                            $parts[] = 'Level: '.Level::query()->find($data['level_id'])?->name;
                        }

                        if (filled($data['competency_area_id'] ?? null)) {
                            $parts[] = 'Area: '.CompetencyArea::query()->find($data['competency_area_id'])?->name;
                        }

                        return $parts;
                    }),
            ])
            ->recordActions([
                // Re-evaluates every assignment rule for this person. The case
                // this exists for is somebody moving department and needing
                // their new department's training without an administrator
                // hand-assigning it.
                Action::make('syncTraining')
                    ->label('Sync assigned training')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Applies every active assignment rule that matches this employee.')
                    ->action(function (User $record, SyncAssignmentRules $sync): void {
                        $created = $sync->forUser($record);

                        Notification::make()
                            ->success()
                            ->title($created === 0
                                ? 'Already up to date'
                                : $created.' course'.($created === 1 ? '' : 's').' assigned')
                            ->send();
                    }),

                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Reactivate')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record) => $record->is_active
                        ? 'They will be signed out on their next request. Their training record is kept.'
                        : 'They will be able to sign in again.')
                    ->action(fn (User $record) => $record->update(['is_active' => ! $record->is_active])),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('name');
    }
}
