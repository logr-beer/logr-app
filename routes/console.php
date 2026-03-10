<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('untappd:sync')->daily()->at('03:00');

if (env('DEMO_MODE')) {
    Schedule::command('demo:reset')->everyThirtyMinutes();
}
