<?php

namespace App\Filament\Resources\CourseEnrollments\Schemas;

use App\Enums\EnrollmentSource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseEnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                Select::make('source')
                    ->options(EnrollmentSource::class)
                    ->default('manual')
                    ->required(),
                Select::make('assignment_rule_id')
                    ->relationship('assignmentRule', 'id'),
                TextInput::make('assigned_by')
                    ->numeric(),
                DateTimePicker::make('enrolled_at')
                    ->required(),
                DateTimePicker::make('due_at'),
            ]);
    }
}
