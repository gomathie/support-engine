<?php

namespace App\Filament\Resources\AssignmentRules;

use App\Filament\Resources\AssignmentRules\Pages\CreateAssignmentRule;
use App\Filament\Resources\AssignmentRules\Pages\EditAssignmentRule;
use App\Filament\Resources\AssignmentRules\Pages\ListAssignmentRules;
use App\Filament\Resources\AssignmentRules\Schemas\AssignmentRuleForm;
use App\Filament\Resources\AssignmentRules\Tables\AssignmentRulesTable;
use App\Models\AssignmentRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssignmentRuleResource extends Resource
{
    protected static ?string $model = AssignmentRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Assignment';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return AssignmentRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssignmentRulesTable::configure($table);
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
            'index' => ListAssignmentRules::route('/'),
            'create' => CreateAssignmentRule::route('/create'),
            'edit' => EditAssignmentRule::route('/{record}/edit'),
        ];
    }
}
