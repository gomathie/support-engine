<?php

namespace App\Filament\Resources\CourseModules\RelationManagers;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Lessons edited on their module. The seeded curriculum arrived as titles with
 * no body — this is where a trainer fills that in.
 */
class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'Lessons';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, $set): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug(Str::limit($state, 60, '')));
                        }
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->options(LessonType::options())
                    ->default(LessonType::RichText->value)
                    ->required()
                    ->live(),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),

                // Sanitised on the way out through HTMLPurifier's `lesson`
                // allowlist, so a compromised author account cannot become
                // stored XSS against every employee.
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

                Select::make('completion_requirement')
                    ->options(CompletionRequirement::options())
                    ->default(CompletionRequirement::Acknowledge->value)
                    ->required(),

                TextInput::make('estimated_minutes')
                    ->label('Estimated duration (minutes)')
                    ->numeric()
                    ->minValue(0),

                Toggle::make('is_published')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->weight('bold')
                    ->wrap()
                    ->description(fn (Lesson $record) => $record->description),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (LessonType $state) => $state->label())
                    ->color('gray'),

                TextColumn::make('completion_requirement')
                    ->label('Completed by')
                    ->formatStateUsing(fn (CompletionRequirement $state) => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('resources_count')
                    ->label('Files')
                    ->counts('resources')
                    ->alignEnd(),

                // A rich-text lesson with no body is a title and nothing else —
                // exactly what the seeded curriculum starts as.
                IconColumn::make('has_content')
                    ->label('Body')
                    ->state(fn (Lesson $record) => filled($record->content) || $record->resources()->exists())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->falseColor('warning')
                    ->tooltip(fn (Lesson $record) => filled($record->content)
                        ? null
                        : 'No content yet — the employee sees only the title.'),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([
                CreateAction::make()->label('Add lesson'),
            ])
            ->recordActions([
                // Files live on the lesson's own screen, where the uploader is.
                Action::make('open')
                    ->label('Files')
                    ->icon('heroicon-o-paper-clip')
                    ->color('gray')
                    ->url(fn (Lesson $record) => LessonResource::getUrl('edit', ['record' => $record])),

                \Filament\Actions\EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No lessons in this module');
    }
}
