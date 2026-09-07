<?php

namespace App\Filament\Resources\Levels\Schemas;

use App\Models\CompetencyArea;
use App\Models\Course;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('The rung')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('position')
                            ->label('Ladder position')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->unique(ignoreRecord: true)
                            ->helperText('1 is the foundation. A rung is never awarded before the one below it in the same area.'),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('What somebody at this rung can actually do, written as a capability rather than a syllabus.'),
                    ]),

                Section::make('What this rung requires')
                    ->description('A level is earned per area, by completing every course listed for that area. An area with no courses here cannot be awarded at all.')
                    ->schema([
                        Repeater::make('requirements')
                            ->relationship()
                            ->hiddenLabel()
                            ->columns(2)
                            ->schema([
                                Select::make('competency_area_id')
                                    ->label('Area')
                                    ->options(fn () => CompetencyArea::query()->orderBy('position')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),

                                Select::make('course_id')
                                    ->label('Required course')
                                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id'))
                                    ->required()
                                    ->searchable(),
                            ])
                            ->itemLabel(fn (array $state): ?string => filled($state['competency_area_id'] ?? null)
                                ? CompetencyArea::query()->find($state['competency_area_id'])?->name
                                : null)
                            ->addActionLabel('Add a required course')
                            ->defaultItems(0)
                            ->reorderable(false),
                    ]),
            ]);
    }
}
