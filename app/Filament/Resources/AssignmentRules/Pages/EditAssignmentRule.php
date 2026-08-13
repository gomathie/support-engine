<?php

namespace App\Filament\Resources\AssignmentRules\Pages;

use App\Filament\Resources\AssignmentRules\AssignmentRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentRule extends EditRecord
{
    protected static string $resource = AssignmentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
