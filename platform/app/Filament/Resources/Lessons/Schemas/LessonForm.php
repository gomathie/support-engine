<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Models\CourseModule;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Placement')
                    ->columns(2)
                    ->schema([
                        Select::make('course_module_id')
                            ->label('Module')
                            ->options(fn () => CourseModule::query()
                                ->with('course:id,title')
                                ->orderBy('course_id')
                                ->orderBy('position')
                                ->get()
                                ->mapWithKeys(fn (CourseModule $m) => [
                                    $m->id => $m->course->title.' — '.$m->title,
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug(Str::limit($state, 60, '')));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('position')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Blank appends to the end.'),

                        Toggle::make('is_published')->default(true),
                    ]),

                Section::make('Content')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->options(LessonType::options())
                            ->default(LessonType::RichText->value)
                            ->required()
                            ->live()
                            ->helperText('Adding a new type is an enum case plus a branch in the viewer.'),

                        Select::make('completion_requirement')
                            ->options(CompletionRequirement::options())
                            ->default(CompletionRequirement::Acknowledge->value)
                            ->required(),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),

                        // Everything authored here is run through HTMLPurifier's
                        // `lesson` allowlist before it is sent to a browser, so
                        // a compromised author account cannot become stored XSS
                        // against every employee.
                        RichEditor::make('content')
                            ->columnSpanFull()
                            ->visible(fn ($get) => in_array(
                                $get('type'),
                                [LessonType::RichText->value, LessonType::Download->value],
                                true,
                            ))
                            ->helperText('Sanitised on save and again on display.'),

                        TextInput::make('external_url')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('type') === LessonType::ExternalLink->value),

                        TextInput::make('estimated_minutes')
                            ->label('Estimated duration (minutes)')
                            ->numeric()
                            ->minValue(0),
                    ]),
            ]);
    }
}
