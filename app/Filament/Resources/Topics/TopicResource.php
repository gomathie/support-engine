<?php

namespace App\Filament\Resources\Topics;

use App\Filament\Resources\Topics\Pages\CreateTopic;
use App\Filament\Resources\Topics\Pages\EditTopic;
use App\Filament\Resources\Topics\Pages\ListTopics;
use App\Filament\Resources\Topics\Schemas\TopicForm;
use App\Filament\Resources\Topics\Tables\TopicsTable;
use App\Models\Topic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TopicForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TopicsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ResourcesRelationManager::class,
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Course' => $record->course?->title ?? '—',
            'Module' => $record->lesson?->title ?? '—',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTopics::route('/'),
            'create' => CreateTopic::route('/create'),
            'edit' => EditTopic::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
