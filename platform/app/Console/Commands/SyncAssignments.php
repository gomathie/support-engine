<?php

namespace App\Console\Commands;

use App\Actions\Enrollment\SyncAssignmentRules;
use Illuminate\Console\Command;

class SyncAssignments extends Command
{
    protected $signature = 'training:sync-assignments';

    protected $description = 'Apply every active assignment rule, enrolling anyone it now matches';

    public function handle(SyncAssignmentRules $sync): int
    {
        $created = $sync->all();

        $this->info($created === 0
            ? 'Everything already up to date.'
            : $created.' new enrollment'.($created === 1 ? '' : 's').' created.');

        return self::SUCCESS;
    }
}
