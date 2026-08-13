<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quiz')
                    ->columns(2)
                    ->schema([
                        Select::make('course_id')
                            ->label('Course')
                            ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                            ->searchable()
                            ->required()
                            ->live(),

                        TextInput::make('title')->required()->maxLength(255),

                        Textarea::make('description')->rows(2)->columnSpanFull(),

                        // A quiz attached to neither is the course's final
                        // assessment. Attaching it to a module or a lesson makes
                        // it an end-of-module test or a knowledge check.
                        Select::make('course_module_id')
                            ->label('Module (optional)')
                            ->options(fn ($get) => $get('course_id')
                                ? CourseModule::query()
                                    ->where('course_id', $get('course_id'))
                                    ->orderBy('position')
                                    ->pluck('title', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->helperText('Leave both blank to make this the final assessment.'),

                        Select::make('lesson_id')
                            ->label('Lesson (optional)')
                            ->options(fn ($get) => $get('course_id')
                                ? Lesson::query()
                                    ->where('course_id', $get('course_id'))
                                    ->orderBy('position')
                                    ->pluck('title', 'id')
                                    ->all()
                                : [])
                            ->searchable(),
                    ]),

                Section::make('Rules')
                    ->columns(3)
                    ->schema([
                        TextInput::make('passing_score')
                            ->label('Pass mark (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(70)
                            ->required()
                            ->helperText('Snapshotted onto each attempt, so raising it later cannot retroactively fail anyone.'),

                        TextInput::make('max_attempts')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Blank = unlimited.'),

                        TextInput::make('time_limit_minutes')
                            ->label('Time limit (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Blank = untimed. Enforced server-side.'),

                        Toggle::make('shuffle_questions'),
                        Toggle::make('shuffle_options'),
                        Toggle::make('show_feedback')
                            ->default(true)
                            ->helperText('Show which questions were wrong, and the explanation, after submitting.'),

                        Toggle::make('is_published')->columnSpanFull(),
                    ]),

                Section::make('Questions')
                    ->description(
                        'Correct answers live only here. They are stripped from the payload the '
                        .'employee\'s browser receives, and are only revealed on the results screen '
                        .'after an attempt has been graded.'
                    )
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->orderColumn('position')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['prompt'] ?? null)
                            ->addActionLabel('Add question')
                            ->defaultItems(0)
                            ->columns(2)
                            ->schema([
                                Select::make('type')
                                    ->options(QuestionType::options())
                                    ->default(QuestionType::SingleChoice->value)
                                    ->required()
                                    ->live(),

                                TextInput::make('points')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                Textarea::make('prompt')
                                    ->rows(2)
                                    ->required()
                                    ->columnSpanFull(),

                                Textarea::make('explanation')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->helperText('Shown after grading, when feedback is enabled.'),

                                Repeater::make('options')
                                    ->relationship()
                                    ->orderColumn('position')
                                    ->columnSpanFull()
                                    ->addActionLabel('Add option')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('label')
                                            ->required()
                                            ->columnSpan(3),

                                        Checkbox::make('is_correct')
                                            ->label('Correct')
                                            ->columnSpan(1),
                                    ])
                                    ->helperText(fn ($get) => $get('type') === QuestionType::ShortAnswer->value
                                        ? 'For short answer these are the accepted answers — tick every one, matching is case-insensitive.'
                                        : 'Tick every correct option. Multiple choice is graded all-or-nothing.'),
                            ]),
                    ]),
            ]);
    }
}
