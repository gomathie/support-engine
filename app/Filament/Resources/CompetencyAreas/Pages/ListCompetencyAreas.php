<?php

namespace App\Filament\Resources\CompetencyAreas\Pages;

use App\Filament\Resources\CompetencyAreas\CompetencyAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompetencyAreas extends ListRecords
{
    protected static string $resource = CompetencyAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
