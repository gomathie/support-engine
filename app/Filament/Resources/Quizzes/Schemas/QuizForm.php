<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Quiz;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuizForm
{
    /**
     * What a quiz is attached to used to be expressed by leaving two selects
     * blank, which nobody could be expected to guess. It is now an explicit
     * choice, and the selects appear only when they are relevant.
     *
     * The field is not persisted — the scope *is* the pair of foreign keys, and
     * storing it twice would let the two disagree. CreateQuiz and EditQuiz
     * translate it back into those keys on save.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('What is this assessment for?')
                    ->schema([
                        Select::make('course_id')
                            ->label('Course')
                            ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                            ->searchable()
                            ->required()
                            ->live(),

                        Radio::make('scope')
                            ->hiddenLabel()
                            ->options([
                                Quiz::SCOPE_FINAL => 'Final exam for the whole course',
                                Quiz::SCOPE_MODULE => 'End-of-module test',
                                Quiz::SCOPE_LESSON => 'Knowledge check on one lesson',
                            ])
                            ->descriptions([
                                Quiz::SCOPE_FINAL => 'Unlocks once every lesson is complete. Passing it '
                                    .'completes the course and issues the certificate.',
                                Quiz::SCOPE_MODULE => 'Sits at the end of one module.',
                                Quiz::SCOPE_LESSON => 'Attached to a single lesson. Set that lesson\'s '
                                    .'completion requirement to "quiz" to make it mandatory.',
                            ])
                            ->default(Quiz::SCOPE_FINAL)
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Radio $component, ?Quiz $record): void {
                                $component->state($record?->scope() ?? Quiz::SCOPE_FINAL);
                            }),

                        Select::make('course_module_id')
                            ->label('Module')
                            ->options(fn (Get $get) => $get('course_id')
                                ? CourseModule::query()
                                    ->where('course_id', $get('course_id'))
                                    ->orderBy('position')
                                    ->pluck('title', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->required()
                            ->visible(fn (Get $get) => $get('scope') === Quiz::SCOPE_MODULE),

                        Select::make('lesson_id')
                            ->label('Lesson')
                            ->options(fn (Get $get) => $get('course_id')
                                ? Lesson::query()
                                    ->where('course_id', $get('course_id'))
                                    ->orderBy('position')
                                    ->pluck('title', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->required()
                            ->visible(fn (Get $get) => $get('scope') === Quiz::SCOPE_LESSON),
                    ]),

                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Shown to the employee before they begin.'),
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
                            ->helperText('Snapshotted onto each attempt, so raising it later cannot '
                                .'retroactively fail anyone.'),

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
                            ->helperText('Show which questions were wrong, and the explanation, after '
                                .'submitting.'),

                        Toggle::make('is_published')
                            ->columnSpanFull()
                            ->helperText('Unpublished assessments are invisible to employees, and a '
                                .'course with an unpublished final exam completes on lessons alone.'),
                    ]),

                Section::make('Questions')
                    ->description(
                        'Correct answers live only here. They are stripped from the payload the '
                        .'employee\'s browser receives, and are revealed on the results screen only '
                        .'after an attempt has been graded.'
                    )
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->orderColumn('position')
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['prompt'] ?? 'New question')
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
                                    ->defaultItems(2)
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('label')
                                            ->hiddenLabel()
                                            ->placeholder('Answer text')
                                            ->required()
                                            ->columnSpan(3),

                                        Checkbox::make('is_correct')
                                            ->label('Correct')
                                            ->columnSpan(1),
                                    ])
                                    ->helperText(fn (Get $get) => $get('type') === QuestionType::ShortAnswer->value
                                        ? 'For short answer these are the accepted answers — add every '
                                          .'acceptable wording and tick them all. Matching ignores case '
                                          .'and extra spacing.'
                                        : 'Tick every correct option. Multiple choice is graded '
                                          .'all-or-nothing, so a partly-right answer scores zero.'),
                            ]),
                    ]),
            ]);
    }
}
