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
use App\Mail\DocumentosAtrasadosMail;
use App\Mail\ReservasDiariasMail;
use Carbon\Carbon;


// ===========================================================
// 1. Notificación de cumpleaños diarios
// ===========================================================

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



// ===========================================================
// 1. Notificación fechas vencida por semana
// ===========================================================


Schedule::call(function () {
    // 📆 Calcular lunes y domingo de la semana actual
    $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    $fin = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

    // 🔹 VENTAS (RCV_VENTAS)
    $ventas = DocumentoFinanciero::whereBetween('fecha_vencimiento', [$inicio, $fin])
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');

    // 🔸 COMPRAS (RCV_COMPRAS)
    $compras = DocumentoCompra::whereBetween('fecha_vencimiento', [$inicio, $fin])
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');

    if ($ventas->isEmpty() && $compras->isEmpty()) {
        Log::info('📭 [VENCIMIENTOS] No hay documentos (ventas ni compras) por vencer esta semana.');
        return;
    }

    // 📨 Enviar correo con ambas colecciones
    $destinos = [
        'eliascorrea@4nlogistica.cl',
        'NataliaLeyton@4nlogistica.cl',
        'hansdelabarra@4nlogistica.cl',
        'marcelo@4nlogistica.cl',
    ];

    Mail::to($destinos)->send(new DocumentosVencimientosNotificacion($ventas, $compras));

    Log::info('✅ [VENCIMIENTOS] Correo de vencimientos enviado a (' . implode(', ', $destinos) . ') - Rango: ' .
        $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y'));
})
->weeklyOn(1, '07:00');// Lunes 07:00 de la mañana




// ===========================================================
// 1. Notificación de fechas vencidas
// ===========================================================

// 📬 Notificación de documentos vencidos con saldo pendiente
Schedule::call(function () {
    $hoy = now()->startOfDay();

    // 🔹 VENTAS
    $ventas = \App\Models\DocumentoFinanciero::where('fecha_vencimiento', '<', $hoy)
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');


    // 🔸 COMPRAS
    $compras = \App\Models\DocumentoCompra::where('fecha_vencimiento', '<', $hoy)
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');

    if ($ventas->isEmpty() && $compras->isEmpty()) {
        Log::info('📭 [ATRASADOS] No hay documentos vencidos con saldo pendiente.');
        return;
    }

    $destinos = [
        'eliascorrea@4nlogistica.cl',
        'NataliaLeyton@4nlogistica.cl',
        'hansdelabarra@4nlogistica.cl',
        'marcelo@4nlogistica.cl',

    ];



    Mail::to($destinos)->send(new DocumentosAtrasadosMail($ventas, $compras));

    Log::info('✅ [ATRASADOS] Correo de documentos vencidos enviado a (' . implode(', ', $destinos) . ')');
})
->weeklyOn(1, '07:00');


//////////////////    ////////////////////////////
//////////////////    ////////////////////////////
Schedule::call(function () {

    Log::info("⏳ Ejecutando envío diario de ReservasDiarias...");

    $mensaje = "HOLA

    Por favor solicito reservas para hoy según detalló
    tanto como para despacho y regreso.

    para hoy ENVIO";

    $mensaje_tabular = "
    DESTINO   KG APROX   Vuelo   Estándar   Tipo de carga
    SCL ARICA         10    OV    CARGA GENERAL
    SCL IQUIQUE       10    OV    CARGA GENERAL
    SCL ANTOFAGASTA   10    OV    CARGA GENERAL
    SCL CALAMA        10    OV    CARGA GENERAL
    SCL PUNTA ARENAS  10    OV    CARGA GENERAL
    SCL BALMACEDA     10    OV    CARGA GENERAL
    ";

    Mail::to(['eliascorrea@4nlogistica.cl', 'hansdelabarra@4nlogistica.cl'])
        ->send(new ReservasDiariasMail($mensaje, $mensaje_tabular));

    Log::info("✅ Correo de ReservasDiarias enviado a las 8:00 (Lun-Vie)");

})
->weekdays()->at('08:00');








Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
