<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Trabajador;
use App\Mail\CumpleaniosNotificacion;
use Illuminate\Support\Facades\Mail;

Schedule::call(function () {
    $hoy = now()->format('m-d');

    $cumpleanieros = Trabajador::whereRaw("DATE_FORMAT(FechaNacimiento, '%m-%d') = ?", [$hoy])->get();

    if ($cumpleanieros->isNotEmpty()) {
        $destinos = [
            'elizabeth.obreque@4nlogistica.cl',
            'hansdelabarra@4nlogistica.cl',
        ];
        Mail::to($destinos)->send(new CumpleaniosNotificacion($cumpleanieros));
    }
})->dailyAt('00:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
