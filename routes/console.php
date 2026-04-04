<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup scheduled tasks
Schedule::command('backup:run')->daily()->at('00:00');
Schedule::command('backup:clean')->daily()->at('01:00');
