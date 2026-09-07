<?php

namespace App\Filament\Resources\PracticalTasks\Pages;

use App\Filament\Resources\PracticalTasks\PracticalTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPracticalTask extends EditRecord
{
    protected static string $resource = PracticalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
