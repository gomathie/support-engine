<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DepartmentForm
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
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),

                Select::make('managers')
                    ->relationship('managers', 'name', fn ($query) => $query->role(Role::Manager->value))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->helperText('Managers see the employees and reports for the departments they run — and no others.'),
            ]);
    }
}
