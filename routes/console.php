<?php

use App\Jobs\SendReminders;
use App\Models\MagicLink;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SendReminders)->dailyAt('08:00');

// Nettoyage des magic links expirés
Schedule::call(fn() => MagicLink::where('expires_at', '<', now())->delete())->daily();
