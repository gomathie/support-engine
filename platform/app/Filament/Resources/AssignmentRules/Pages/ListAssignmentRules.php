<?php

namespace App\Filament\Resources\AssignmentRules\Pages;

use App\Filament\Resources\AssignmentRules\AssignmentRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssignmentRules extends ListRecords
{
    protected static string $resource = AssignmentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
