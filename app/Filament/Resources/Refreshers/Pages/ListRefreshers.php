<?php

namespace App\Filament\Resources\Refreshers\Pages;

use App\Filament\Resources\Refreshers\RefresherResource;
use Filament\Resources\Pages\ListRecords;

class ListRefreshers extends ListRecords
{
    protected static string $resource = RefresherResource::class;

    public function getSubheading(): ?string
    {
        return 'Five questions, thirty and ninety days after a level was awarded. Nothing here '
            .'grants or removes anything — a low score is a finding about the teaching, not a '
            .'verdict on the person. Retention is the score as a percentage of the exam that '
            .'earned the level, and feeds KPI 6.';
    }
}
