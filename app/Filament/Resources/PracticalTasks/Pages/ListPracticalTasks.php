<?php

namespace App\Filament\Resources\PracticalTasks\Pages;

use App\Filament\Resources\PracticalTasks\PracticalTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPracticalTasks extends ListRecords
{
    protected static string $resource = PracticalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
