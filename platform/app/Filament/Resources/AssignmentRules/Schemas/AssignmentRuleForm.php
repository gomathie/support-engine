<?php

namespace App\Filament\Resources\AssignmentRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssignmentRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                TextInput::make('target_type')
                    ->required(),
                TextInput::make('target_id')
                    ->numeric(),
                TextInput::make('target_value'),
                TextInput::make('due_days')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('applies_retroactively')
                    ->required(),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
