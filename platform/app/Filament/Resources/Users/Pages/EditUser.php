<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Enrollment\SyncAssignmentRules;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    private ?int $departmentBeforeSave = null;

    protected function beforeSave(): void
    {
        $this->departmentBeforeSave = $this->record->department_id;
    }

    /**
     * Moving somebody between departments should pick up the new department's
     * training. Nothing is un-assigned: training already started stays on their
     * record, because half-finished progress is not something to silently throw
     * away.
     */
    protected function afterSave(): void
    {
        if ($this->record->department_id === $this->departmentBeforeSave) {
            return;
        }

        $created = app(SyncAssignmentRules::class)->forUser($this->record);

        if ($created > 0) {
            Notification::make()
                ->success()
                ->title($created.' new course'.($created === 1 ? '' : 's').' assigned')
                ->body('Their previous department\'s training has been left in place.')
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
