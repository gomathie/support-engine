<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    /** @see CreateQuiz::mutateFormDataBeforeCreate() */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return QuizResource::applyScope($data, $this->data['scope'] ?? Quiz::SCOPE_FINAL);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
