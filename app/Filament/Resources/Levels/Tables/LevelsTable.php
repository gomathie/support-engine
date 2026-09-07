<?php

namespace App\Filament\Resources\Levels\Tables;

use App\Models\Level;
use App\Models\TraineeLevel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LevelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Level $record) => $record->description),

                TextColumn::make('requirements_count')
                    ->label('Required courses')
                    ->counts('requirements')
                    ->alignEnd(),

                // A rung configured for no areas is inert: nobody can earn it.
                // Worth showing plainly while the Phase 1 audit fills them in.
                TextColumn::make('areas')
                    ->label('Areas configured')
                    ->state(fn (Level $record) => $record->requirements()
                        ->distinct()
                        ->count('competency_area_id'))
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->alignEnd(),

                TextColumn::make('held_by')
                    ->label('Currently held by')
                    ->state(fn (Level $record) => TraineeLevel::query()
                        ->where('level_id', $record->getKey())
                        ->whereNull('revoked_at')
                        ->count())
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
