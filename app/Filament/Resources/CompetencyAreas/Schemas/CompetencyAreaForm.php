<?php

namespace App\Filament\Resources\CompetencyAreas\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CompetencyAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, $set): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('position')
                    ->label('Display order')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('The work this area covers, in the words a support engineer would use.'),
            ]);
    }
}
