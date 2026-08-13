<?php

namespace App\Filament\Resources\CourseEnrollments\Schemas;

use App\Enums\EnrollmentSource;
use App\Models\Course;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class CourseEnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('user_id')
                    ->label('Employee')
                    ->options(fn () => User::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),

                Select::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->visible()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable()
                    ->required(),

                Select::make('source')
                    ->options(EnrollmentSource::options())
                    ->default(EnrollmentSource::Manual->value)
                    ->required(),

                DateTimePicker::make('due_at')
                    ->label('Due')
                    ->helperText('Blank uses the course default, or no deadline at all.'),
            ]);
    }
}
