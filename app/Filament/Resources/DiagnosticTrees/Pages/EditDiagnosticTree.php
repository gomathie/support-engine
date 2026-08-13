<?php

namespace App\Filament\Resources\DiagnosticTrees\Pages;

use App\Filament\Resources\DiagnosticTrees\DiagnosticTreeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDiagnosticTree extends EditRecord
{
    protected static string $resource = DiagnosticTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
