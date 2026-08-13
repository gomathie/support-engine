<?php

namespace App\Filament\Resources\DiagnosticTrees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiagnosticTreesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label('Symptom')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('layer_label')
                    ->label('Layers')
                    ->badge(),

                TextColumn::make('steps_count')
                    ->label('Steps')
                    ->counts('steps')
                    ->alignEnd(),

                TextColumn::make('support_cases_count')
                    ->label('Cases worked')
                    ->counts('supportCases')
                    ->alignEnd()
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
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
