<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Lessons\LessonResource;
use App\Models\CourseModule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Modules edited on the course itself, rather than by navigating to a separate
 * resource and picking the parent from a dropdown. Reordering here is what
 * decides the order an employee works through the course.
 */
class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $title = 'Modules';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The short label — "Day 1", "Module A", "Week 2".'),

                TextInput::make('subtitle')
                    ->maxLength(255)
                    ->helperText('The longer heading shown beside the label.'),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('The topics line under the module heading.'),

                Toggle::make('is_published')
                    ->default(true)
                    ->columnSpanFull()
                    ->helperText('Unpublished modules and their lessons do not count toward progress.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->weight('bold')
                    ->description(fn (CourseModule $record) => $record->subtitle),

                TextColumn::make('lessons_count')
                    ->label('Lessons')
                    ->counts('lessons')
                    ->alignEnd(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            // Drag to reorder — this is the order employees see.
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([
                CreateAction::make()->label('Add module'),
            ])
            ->recordActions([
                // Lessons live on the module, so the natural next step from
                // here is the module's own edit screen rather than a nested
                // repeater four levels deep.
                Action::make('lessons')
                    ->label('Lessons')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (CourseModule $record) => LessonResource::getUrl('index', [
                        'tableFilters' => ['course_id' => ['value' => $record->course_id]],
                    ])),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No modules yet')
            ->emptyStateDescription('A course is made of modules, and each module holds the lessons.');
    }
}
