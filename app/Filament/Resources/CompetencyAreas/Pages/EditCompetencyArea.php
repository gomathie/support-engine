<?php

namespace App\Filament\Resources\CompetencyAreas\Pages;

use App\Filament\Resources\CompetencyAreas\CompetencyAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompetencyArea extends EditRecord
{
    protected static string $resource = CompetencyAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
