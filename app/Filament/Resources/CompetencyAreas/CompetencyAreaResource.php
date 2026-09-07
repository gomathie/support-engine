<?php

namespace App\Filament\Resources\CompetencyAreas;

use App\Filament\Resources\CompetencyAreas\Pages\CreateCompetencyArea;
use App\Filament\Resources\CompetencyAreas\Pages\EditCompetencyArea;
use App\Filament\Resources\CompetencyAreas\Pages\ListCompetencyAreas;
use App\Filament\Resources\CompetencyAreas\Schemas\CompetencyAreaForm;
use App\Filament\Resources\CompetencyAreas\Tables\CompetencyAreasTable;
use App\Models\CompetencyArea;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The domains a level is held in.
 *
 * Levels are per area because somebody can be competent at reporting and not at
 * devices; a single overall level would hide exactly the gap a trainer needs to
 * see. Admin-authored, for the same reason as the rungs themselves.
 */
class CompetencyAreaResource extends Resource
{
    protected static ?string $model = CompetencyArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static string|UnitEnum|null $navigationGroup = 'Competency';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CompetencyAreaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompetencyAreasTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->can('competency.manage') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Filament::auth()->user()?->can('competency.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Filament::auth()->user()?->can('competency.manage') ?? false;
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
            'index' => ListCompetencyAreas::route('/'),
            'create' => CreateCompetencyArea::route('/create'),
            'edit' => EditCompetencyArea::route('/{record}/edit'),
        ];
    }
}
