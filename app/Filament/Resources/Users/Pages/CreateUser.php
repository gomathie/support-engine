<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Enrollment\SyncAssignmentRules;
use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * A new starter should already have their department's training waiting for
     * them the first time they sign in, without an administrator remembering to
     * assign it by hand.
     */
    protected function afterCreate(): void
    {
        $created = app(SyncAssignmentRules::class)->forUser($this->record);

        if ($created > 0) {
            Notification::make()
                ->success()
                ->title($created.' course'.($created === 1 ? '' : 's').' assigned automatically')
                ->body('From the assignment rules matching their department and role.')
                ->send();
        }
    }
}
