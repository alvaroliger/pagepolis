<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pagepolis:suspend-expired')->daily();
Schedule::command('pagepolis:expiry-reminders')->dailyAt('09:00'); // Aviso 7 días antes
Schedule::command('pagepolis:weekly-reports')->weeklyOn(1, '08:00'); // Lunes 8:00
Schedule::command('pagepolis:onboarding-nudges')->dailyAt('10:00'); // Nudge a usuarios sin web a las 48 h

// Limpieza de imágenes adjuntas a la IA que quedaran huérfanas (el job las borra
// al terminar; esto cubre el caso de jobs que no llegaron a ejecutarse).
Schedule::call(function () {
    $disk = \Illuminate\Support\Facades\Storage::disk('local');
    foreach ($disk->files('ai-uploads') as $file) {
        if ($disk->lastModified($file) < now()->subDay()->timestamp) {
            $disk->delete($file);
        }
    }
})->daily()->name('pagepolis:limpiar-subidas-ia')->withoutOverlapping();
