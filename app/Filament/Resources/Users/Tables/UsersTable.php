<?php

namespace App\Filament\Resources\Users\Tables;

use App\Actions\Enrollment\SyncAssignmentRules;
use App\Enums\ProgressStatus;
use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

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
                        Role::Manager->value => 'warning',
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
