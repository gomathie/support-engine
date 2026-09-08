<?php

namespace App\Filament\Resources\Refreshers\Tables;

use App\Enums\RefresherStatus;
use App\Models\CompetencyArea;
use App\Models\Refresher;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RefreshersTable
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
                    ->description(fn (Refresher $record) => $record->user?->department?->name),

                TextColumn::make('traineeLevel.competencyArea.name')
                    ->label('Area')
                    ->searchable()
                    ->description(fn (Refresher $record) => $record->traineeLevel?->level?->name),

                TextColumn::make('interval_days')
                    ->label('Interval')
                    ->formatStateUsing(fn (int $state) => $state.' days')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RefresherStatus $state) => $state->label())
                    ->color(fn (RefresherStatus $state) => match ($state) {
                        RefresherStatus::Completed => 'success',
                        RefresherStatus::Missed => 'gray',
                        RefresherStatus::Scheduled => 'warning',
                    }),

                TextColumn::make('due_at')
                    ->label('Due')
                    ->date()
                    ->sortable()
                    ->description(fn (Refresher $record) => $record->status === RefresherStatus::Scheduled
                        ? 'closes '.$record->closesAt()->toFormattedDateString()
                        : null),

                TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn (?string $state) => $state === null ? '—' : round((float) $state).'%')
                    ->alignEnd()
                    ->sortable(),

                /*
                 * The column the whole feature exists for.
                 *
                 * Over 75% of the original is the §7 target for KPI 6. A dash
                 * means there is nothing to compare against — a hand-granted
                 * level has no exam score behind it — which is deliberately
                 * distinct from a low percentage.
                 */
                TextColumn::make('retention')
                    ->label('Retention')
                    ->formatStateUsing(fn (?string $state) => $state === null ? '—' : round((float) $state).'%')
                    ->badge()
                    ->color(fn (?string $state) => match (true) {
                        $state === null => 'gray',
                        (float) $state > 75 => 'success',
                        default => 'warning',
                    })
                    ->alignEnd()
                    ->sortable()
                    ->tooltip('The refresher score as a percentage of the exam that earned the level. Target: over 75%.'),
            ])

            ->defaultSort('due_at', 'desc')

            ->filters([
                SelectFilter::make('status')
                    ->options(RefresherStatus::options()),

                SelectFilter::make('interval_days')
                    ->label('Interval')
                    ->options([30 => '30 days', 90 => '90 days']),

                SelectFilter::make('competency_area')
                    ->label('Area')
                    ->options(fn () => CompetencyArea::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('traineeLevel', fn ($q) => $q->where('competency_area_id', $data['value']))
                        : $query),

                // The actionable one: due, still open, and nobody has sat it.
                Filter::make('outstanding')
                    ->label('Outstanding now')
                    ->query(fn ($query) => $query->open()),

                /*
                 * Retention below the §7 target.
                 *
                 * This is the list worth reading. A cluster of these in one
                 * area is a finding about the material rather than about the
                 * people — knowledge fades on a schedule, and the fix is
                 * reinforcement, not a harder exam.
                 */
                Filter::make('fading')
                    ->label('Below the retention target')
                    ->query(fn ($query) => $query->whereNotNull('retention')->where('retention', '<=', 75)),
            ]);
    }
}
