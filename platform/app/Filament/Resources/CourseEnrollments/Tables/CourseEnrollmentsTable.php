<?php

namespace App\Filament\Resources\CourseEnrollments\Tables;

use App\Enums\EnrollmentSource;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseEnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (CourseEnrollment $record) => $record->user?->department?->name),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (EnrollmentSource $state) => match ($state) {
                        EnrollmentSource::Manual => 'Manual',
                        EnrollmentSource::Department => 'Department',
                        EnrollmentSource::RoleBased => 'Role',
                        EnrollmentSource::Rule => 'Rule',
                        EnrollmentSource::Self => 'Self',
                    })
                    ->color(fn (EnrollmentSource $state) => $state === EnrollmentSource::Rule ? 'info' : 'gray'),

                // Read from the rollup rather than recomputed, so this table is
                // one extra query rather than one per row.
                TextColumn::make('progress')
                    ->label('Progress')
                    ->state(function (CourseEnrollment $record): string {
                        $progress = CourseProgress::query()
                            ->where('user_id', $record->user_id)
                            ->where('course_id', $record->course_id)
                            ->first();

                        if (! $progress) {
                            return '—';
                        }

                        return round((float) $progress->percentage).'%';
                    })
                    ->alignEnd(),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (CourseEnrollment $record) => CourseProgress::query()
                        ->where('user_id', $record->user_id)
                        ->where('course_id', $record->course_id)
                        ->value('status') ?? ProgressStatus::NotStarted->value)
                    ->badge()
                    ->formatStateUsing(fn ($state) => ProgressStatus::from($state)->label())
                    ->color(fn ($state) => match (ProgressStatus::from($state)) {
                        ProgressStatus::Completed => 'success',
                        ProgressStatus::InProgress => 'info',
                        ProgressStatus::Failed, ProgressStatus::Overdue => 'danger',
                        ProgressStatus::NotStarted => 'gray',
                    }),

                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('j M Y')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn (CourseEnrollment $record) => $record->isOverdue() ? 'danger' : null),

                TextColumn::make('enrolled_at')
                    ->label('Assigned')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                SelectFilter::make('department')
                    ->label('Department')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $id) => $q->whereHas('user', fn ($u) => $u->where('department_id', $id)),
                    )),

                SelectFilter::make('source')->options(EnrollmentSource::options()),

                Filter::make('overdue')
                    ->label('Overdue only')
                    ->query(fn ($query) => $query->overdue()),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Unassign')
                    ->modalDescription(
                        'The enrollment is soft-deleted and their progress is kept, so re-assigning '
                        .'this course later picks up where they left off.'
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Unassign selected'),
                ]),
            ])
            ->defaultSort('enrolled_at', 'desc');
    }
}
