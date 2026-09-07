<?php

namespace App\Filament\Resources\PracticalTasks\Schemas;

use App\Models\Course;
use App\Models\Topic;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PracticalTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Placement')
                    ->columns(2)
                    ->schema([
                        Select::make('course_id')
                            ->label('Course')
                            ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('topic_id')
                            ->label('Topic (optional)')
                            ->options(fn ($get) => $get('course_id')
                                ? Topic::query()
                                    ->where('course_id', $get('course_id'))
                                    ->orderBy('position')
                                    ->pluck('title', 'id')
                                : [])
                            ->searchable()
                            ->placeholder('Stands for the whole course')
                            ->helperText('Attaching it to a topic puts it directly after that topic.'),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug(Str::limit((string) $state, 60, '')));
                                }
                            }),

                        TextInput::make('slug')->required()->maxLength(255),

                        TextInput::make('estimated_minutes')
                            ->label('Estimated duration (minutes)')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('position')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Toggle::make('is_published')
                            ->helperText('Unpublished tasks are invisible to trainees.'),
                    ]),

                Section::make('The task')
                    ->schema([
                        RichEditor::make('brief')
                            ->label('Brief')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('A realistic scenario with the noise a real ticket contains. Sanitised on save and again on display.'),

                        Textarea::make('submission_instructions')
                            ->label('How to submit')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('expected_evidence')
                            ->label('Evidence required')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('What must be attached or shown. Verification is a scored criterion, so say plainly what counts as proof.'),
                    ]),

                /*
                 * Trainees work in a real PILOT account, so a task that says
                 * "create an object" leaves a real object behind with a real
                 * agent ID. Asking for it turns the submission from a claim into
                 * something the marker can go and check.
                 */
                Section::make('Verifiable evidence')
                    ->description('Identifiers the trainee must supply. They work in a live PILOT account, so what they create can be looked up — which is the difference between "I did it" and proof.')
                    ->schema([
                        Toggle::make('requires_screenshot')
                            ->label('Require a screenshot')
                            ->helperText('The submission cannot be handed in with nothing attached.'),

                        Repeater::make('required_evidence')
                            ->label('Identifiers to collect')
                            ->hiddenLabel()
                            ->columns(3)
                            ->schema([
                                TextInput::make('key')
                                    ->label('Field key')
                                    ->required()
                                    ->alphaDash()
                                    ->maxLength(40)
                                    ->placeholder('agent_id')
                                    ->helperText('Stored on the submission. Lower case, no spaces.'),

                                TextInput::make('label')
                                    ->label('Shown to the trainee')
                                    ->required()
                                    ->maxLength(120)
                                    ->placeholder('Agent ID (vehicle ID)'),

                                TextInput::make('hint')
                                    ->label('Hint')
                                    ->maxLength(160)
                                    ->placeholder('Object card → Info tab'),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Add an identifier')
                            ->defaultItems(0)
                            ->helperText('Common ones: agent ID for a vehicle, account ID for a user, contract ID, sensor name.'),
                    ]),

                Section::make('Marking')
                    ->description('Scored against the standard four-criterion rubric: Correctness · Method · Verification · Communication, 0–4 each. Pass needs 3+ on Correctness, 2+ on the rest, and 10+ overall.')
                    ->schema([
                        Toggle::make('requires_second_marker')
                            ->label('Require two independent markers')
                            ->helperText('Turn this on for the first submissions on a new task. Two trainers mark it without seeing each other\'s scores, and it does not settle until they agree — which is what stops the rubric drifting. Turn it off once the standard is calibrated.'),
                    ]),
            ]);
    }
}
