<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Envio automático diário de alertas de documentos atrasados via WhatsApp (OpenWA)
Schedule::command('whatsapp:notify-overdue')->dailyAt('08:00');
