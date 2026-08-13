<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Enums\CourseStatus;
use App\Enums\Role;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Course')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            // Only auto-fill the slug while it is still being
                            // created. Changing the slug of a published course
                            // would break every link anyone has bookmarked.
                            ->afterStateUpdated(function (string $operation, $state, $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Appears in the URL. Changing it breaks existing links.'),

                        TextInput::make('category')
                            ->maxLength(255)
                            ->datalist(fn () => \App\Models\Course::query()
                                ->whereNotNull('category')
                                ->distinct()
                                ->pluck('category')
                                ->all())
                            ->helperText('Shown as the flag chip on the tracker, e.g. TRACK 1.'),

                        Select::make('instructor_id')
                            ->label('Instructor')
                            ->relationship(
                                'instructor',
                                'name',
                                fn ($query) => $query->role([Role::Admin->value, Role::Manager->value]),
                            )
                            ->searchable()
                            ->preload(),

                        TextInput::make('summary')
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText('One line, shown on the course card.'),

                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),

                        FileUpload::make('thumbnail_path')
                            ->image()
                            ->disk('public')
                            ->directory('course-thumbnails')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),

                Section::make('Delivery')
                    ->columns(3)
                    ->schema([
                        Select::make('status')
                            ->options(CourseStatus::options())
                            ->default(CourseStatus::Draft->value)
                            ->required()
                            ->helperText('Drafts are invisible to employees, even by direct URL.'),

                        Select::make('difficulty')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->default('beginner')
                            ->required(),

                        TextInput::make('estimated_minutes')
                            ->label('Estimated duration (minutes)')
                            ->numeric()
                            ->minValue(0),

                        Toggle::make('is_required')
                            ->label('Required training')
                            ->helperText('Required courses cannot be self-enrolled — they are assigned.'),

                        TextInput::make('due_days')
                            ->label('Due within (days of enrollment)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave blank for no deadline.'),
                    ]),
            ]);
    }
}
