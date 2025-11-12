<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Trabajador;
use App\Models\Situacion;
use App\Models\SistemaTrabajo;
use App\Models\Desvinculacion;
use App\Models\DocumentoCompra;
use App\Mail\CumpleaniosNotificacion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentoFinanciero;
use App\Mail\DocumentosVencimientosNotificacion;
use Carbon\Carbon;

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




Schedule::call(function () {
    // 📆 Calcular lunes y domingo de la semana actual
    $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    $fin = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

    // 🔹 VENTAS (RCV_VENTAS)
    $ventas = DocumentoFinanciero::whereBetween('fecha_vencimiento', [$inicio, $fin])
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0);

    // 🔸 COMPRAS (RCV_COMPRAS)
    $compras = DocumentoCompra::whereBetween('fecha_vencimiento', [$inicio, $fin])
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0);

    if ($ventas->isEmpty() && $compras->isEmpty()) {
        Log::info('📭 [VENCIMIENTOS] No hay documentos (ventas ni compras) por vencer esta semana.');
        return;
    }

    // 📨 Enviar correo con ambas colecciones
    $destino = 'eliascorrea@4nlogistica.cl';
    Mail::to($destino)->send(new DocumentosVencimientosNotificacion($ventas, $compras));

    Log::info('✅ [VENCIMIENTOS] Correo de vencimientos enviado (rango: ' .
        $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y') . ')');
})
->weeklyOn(3, '10:30'); // miércoles a las 10:30 AM


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
