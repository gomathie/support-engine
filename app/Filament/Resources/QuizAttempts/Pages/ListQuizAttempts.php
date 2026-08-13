<?php

namespace App\Filament\Resources\QuizAttempts\Pages;

use App\Filament\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListQuizAttempts extends ListRecords
{
    protected static string $resource = QuizAttemptResource::class;

    public function getSubheading(): ?string
    {
        return 'Written and practical answers are marked here. Multiple-choice questions are '
            .'scored automatically on submission.';
    }
}
