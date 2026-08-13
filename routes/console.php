<?php

use App\Console\Commands\SendTrainingReminders;
use Illuminate\Support\Facades\Schedule;

/*
| Requires a scheduler running: `php artisan schedule:work` in development, or
| a single cron entry calling `schedule:run` every minute in production.
*/

// Once a day, on a weekday morning. Weekends are excluded on purpose — a
// reminder nobody can act on until Monday is just noise that trains people to
// ignore the next one.
Schedule::command(SendTrainingReminders::class)
    ->weekdays()
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Picks up people who joined, changed department or changed role since the
// last run, and enrolls them in whatever the assignment rules say they should
// have. Idempotent, so running it often costs nothing.
Schedule::command('training:sync-assignments')
    ->hourly()
    ->withoutOverlapping();
