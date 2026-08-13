<?php

namespace App\Filament\Resources\DiagnosticTrees\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DiagnosticTreeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Symptom')
                    ->columns(2)
                    ->schema([
                        TextInput::make('question')
                            ->label('What the customer says')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('In the customer\'s words — "I can\'t log in", not "authentication failure".')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set): void {
                                if ($operation === 'create') {
                                    $set('key', Str::slug(Str::limit($state, 40, '')));
                                }
                            }),

                        TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('layer_label')
                            ->label('Usual layers')
                            ->maxLength(20)
                            ->placeholder('1–2')
                            ->helperText('Shown beside the symptom in the picker.'),

                        Textarea::make('description')->rows(2)->columnSpanFull(),

                        Toggle::make('is_published')->default(true),
                    ]),

                Section::make('Steps')
                    ->description('Worked top down. The order is the method — put the checks that most often find the fault first.')
                    ->schema([
                        Repeater::make('steps')
                            ->relationship()
                            ->orderColumn('position')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['prompt'] ?? null)
                            ->addActionLabel('Add step')
                            ->columns(4)
                            ->schema([
                                Textarea::make('prompt')
                                    ->label('Check')
                                    ->rows(2)
                                    ->required()
                                    ->columnSpan(3),

                                Select::make('layer')
                                    ->options([
                                        1 => '1 — Access & rights',
                                        2 => '2 — Contract & modules',
                                        3 => '3 — Interface & filters',
                                        4 => '4 — Object config',
                                        5 => '5 — Sensor config',
                                        6 => '6 — Device & data',
                                        7 => '7 — Relay',
                                    ])
                                    ->required()
                                    ->columnSpan(1),

                                Textarea::make('fix')
                                    ->label('What it means when this is the cause')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    // Matches the prototype: the explanation is
                                    // hidden until the agent marks the step as
                                    // the cause, so the tree stays a diagnostic
                                    // exercise rather than a list of answers.
                                    ->helperText('Revealed only once the agent marks this step as "cause found".'),
                            ]),
                    ]),
            ]);
    }
}
