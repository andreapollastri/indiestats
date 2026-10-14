<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup scheduled tasks
Schedule::command('backup:run')->daily()->at('00:00');
Schedule::command('backup:clean')->daily()->at('01:00');

// Updates: the one an administrator asked for starts within a minute; the nightly one
// runs while automatic updates are on (app:update, Updates page).
Schedule::command('app:update pending')->everyMinute()->runInBackground();
Schedule::command('app:update apply --auto')->dailyAt(config('updates.time'))->runInBackground();
