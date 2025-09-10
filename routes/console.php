<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Trabajador;
use App\Models\Situacion;
use App\Models\SistemaTrabajo;
use App\Models\Desvinculacion;
use App\Mail\CumpleaniosNotificacion;
use Illuminate\Support\Facades\Mail;

Schedule::call(function () {
    $hoy = now();

    $cumpleanieros = Trabajador::whereRaw("DATE_FORMAT(FechaNacimiento, '%m-%d') = ?", [$hoy->format('m-d')])
        ->whereHas('sistemaTrabajo', function ($q) {
            $q->where('nombre', '!=', 'Desvinculado');
        })
        ->whereHas('situacion', function ($q) {
            $q->where('Nombre', '!=', 'Desvinculado');
        })
        ->whereNull('deleted_at')
        ->get();

    if ($cumpleanieros->isNotEmpty()) {
        $destinos = [
            'elizabeth.obreque@4nlogistica.cl',
            'hansdelabarra@4nlogistica.cl',
        ];

        Mail::to($destinos)->send(new CumpleaniosNotificacion($cumpleanieros));
    }
})->dailyAt('10:00'); // 🔄 ahora se dispara a las 10:00 AM

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
