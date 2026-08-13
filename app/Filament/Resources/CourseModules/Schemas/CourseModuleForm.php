<?php

namespace App\Filament\Resources\CourseModules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CourseModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The short label — "Day 1", "Module A".'),

                TextInput::make('subtitle')
                    ->maxLength(255)
                    ->helperText('The longer heading shown beside the label.'),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('The topics line under the module heading.'),

                TextInput::make('position')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Leave blank to append to the end. Drag rows on the list to reorder.'),

                Toggle::make('is_published')
                    ->default(true)
                    ->helperText('Unpublished modules and their lessons do not count toward progress.'),
            ]);
    }
}
