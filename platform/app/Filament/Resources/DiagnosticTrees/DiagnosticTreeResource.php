<?php

namespace App\Filament\Resources\DiagnosticTrees;

use App\Filament\Resources\DiagnosticTrees\Pages\CreateDiagnosticTree;
use App\Filament\Resources\DiagnosticTrees\Pages\EditDiagnosticTree;
use App\Filament\Resources\DiagnosticTrees\Pages\ListDiagnosticTrees;
use App\Filament\Resources\DiagnosticTrees\Schemas\DiagnosticTreeForm;
use App\Filament\Resources\DiagnosticTrees\Tables\DiagnosticTreesTable;
use App\Models\DiagnosticTree;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DiagnosticTreeResource extends Resource
{
    protected static ?string $model = DiagnosticTree::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Support panel';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return DiagnosticTreeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiagnosticTreesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticTrees::route('/'),
            'create' => CreateDiagnosticTree::route('/create'),
            'edit' => EditDiagnosticTree::route('/{record}/edit'),
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
