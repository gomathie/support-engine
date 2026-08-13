<?php

namespace App\Filament\Resources\Certificates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CertificateForm
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
                TextInput::make('certificate_number')
                    ->required(),
                TextInput::make('recipient_name')
                    ->required(),
                TextInput::make('course_title')
                    ->required(),
                TextInput::make('score')
                    ->numeric(),
                DateTimePicker::make('completed_at')
                    ->required(),
                DateTimePicker::make('issued_at')
                    ->required(),
                TextInput::make('disk'),
                TextInput::make('path'),
            ]);
    }
}
