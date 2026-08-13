<?php

namespace App\Filament\Resources\DiagnosticTrees\Pages;

use App\Filament\Resources\DiagnosticTrees\DiagnosticTreeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiagnosticTrees extends ListRecords
{
    protected static string $resource = DiagnosticTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
