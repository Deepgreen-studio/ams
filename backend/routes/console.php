<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sync:dispatch-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('monitoring:capture')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('support:evaluate-sla')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('automation:process')->everyMinute()->withoutOverlapping();
Schedule::command('workflows:process-timeouts')->everyMinute()->withoutOverlapping();
Schedule::command('scheduler:process')->everyMinute()->withoutOverlapping();
