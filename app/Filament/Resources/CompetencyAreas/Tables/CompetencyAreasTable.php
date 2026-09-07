<?php

namespace App\Filament\Resources\CompetencyAreas\Tables;

use App\Models\CompetencyArea;
use App\Models\TraineeLevel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompetencyAreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (CompetencyArea $record) => $record->description),

                TextColumn::make('requirements_count')
                    ->label('Requirements')
                    ->counts('requirements')
                    ->alignEnd(),

                // An area nothing is required for cannot be awarded. During the
                // Phase 1 audit most areas sit here, and that should be visible
                // rather than quietly true.
                TextColumn::make('configured')
                    ->label('Awardable')
                    ->state(fn (CompetencyArea $record) => $record->requirements()->exists() ? 'Yes' : 'Not yet')
                    ->badge()
                    ->color(fn ($state) => $state === 'Yes' ? 'success' : 'warning'),

                TextColumn::make('holders')
                    ->label('People holding a level')
                    ->state(fn (CompetencyArea $record) => TraineeLevel::query()
                        ->where('competency_area_id', $record->getKey())
                        ->whereNull('revoked_at')
                        ->distinct()
                        ->count('user_id'))
                    ->alignEnd(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('position')
            ->paginated(false);
    }
}
