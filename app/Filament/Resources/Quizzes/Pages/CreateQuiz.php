<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    /**
     * The scope radio is not a column. Turn it back into the pair of foreign
     * keys, clearing whichever one the chosen scope does not use — otherwise a
     * quiz switched from "module test" to "final exam" would keep its old
     * module id and stay a module test.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return QuizResource::applyScope($data, $this->data['scope'] ?? Quiz::SCOPE_FINAL);
    }
}
