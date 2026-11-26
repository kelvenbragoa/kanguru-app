<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('kanguro:dispatch-due-couriers')->everyMinute();
Schedule::command('kanguro:enforce-order-slas')->everyMinute();
Schedule::command('kanguro:enforce-hotel-slas')->everyMinute();
