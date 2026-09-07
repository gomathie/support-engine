<?php

namespace App\Filament\Resources\PracticalTasks;

use App\Filament\Resources\PracticalTasks\Pages\CreatePracticalTask;
use App\Filament\Resources\PracticalTasks\Pages\EditPracticalTask;
use App\Filament\Resources\PracticalTasks\Pages\ListPracticalTasks;
use App\Filament\Resources\PracticalTasks\Schemas\PracticalTaskForm;
use App\Filament\Resources\PracticalTasks\Tables\PracticalTasksTable;
use App\Models\PracticalTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Authoring practical tasks — the Apply and Analyze end of the Bloom mapping
 * that a multiple-choice question cannot reach.
 *
 * Sits under Content with the other authoring resources, because writing a task
 * is content work; marking the results is a separate screen under Assessment.
 */
class PracticalTaskResource extends Resource
{
    protected static ?string $model = PracticalTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'practical task';

    public static function form(Schema $schema): Schema
    {
        return PracticalTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PracticalTasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPracticalTasks::route('/'),
            'create' => CreatePracticalTask::route('/create'),
            'edit' => EditPracticalTask::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
