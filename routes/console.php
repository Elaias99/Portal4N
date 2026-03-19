<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Trabajador;
use App\Models\DocumentoCompra;
use App\Models\Vacacion;
use App\Services\AutomaticEmailService;
use App\Mail\CumpleaniosNotificacion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentoFinanciero;
use App\Mail\DocumentosVencimientosNotificacion;
use App\Mail\DocumentosAtrasadosMail;
use App\Mail\ReservasDiariasMail;
use Carbon\Carbon;
use App\Mail\VacacionesProximasNotificacion;


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
})->dailyAt('10:00'); // se dispara a las 10:00 AM



// ===========================================================
// 1. Notificación fechas vencida por semana
// ===========================================================


Schedule::call(function () {
    //Calcular lunes y domingo de la semana actual
    $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    $fin = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

    // VENTAS (RCV_VENTAS)
    $ventas = DocumentoFinanciero::whereBetween('fecha_vencimiento', [$inicio, $fin])
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');

    // COMPRAS (RCV_COMPRAS)
    $compras = DocumentoCompra::whereBetween('fecha_vencimiento', [$inicio, $fin])
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');

    if ($ventas->isEmpty() && $compras->isEmpty()) {
        
        return;
    }

    // Enviar correo con ambas colecciones
    $destinos = [
        'eliascorrea@4nlogistica.cl',
        'NataliaLeyton@4nlogistica.cl',
        'hansdelabarra@4nlogistica.cl',
        'marcelo@4nlogistica.cl',
    ];

    Mail::to($destinos)->send(new DocumentosVencimientosNotificacion($ventas, $compras));

})
->cron('0 7 * * 1,5');// Lunes 07:00 de la mañana




// ===========================================================
// 1. Notificación de fechas vencidas
// ===========================================================

// Notificación de documentos vencidos con saldo pendiente
Schedule::call(function () {
    $hoy = now()->startOfDay();

    // VENTAS
    $ventas = \App\Models\DocumentoFinanciero::where('fecha_vencimiento', '<', $hoy)
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');


    // COMPRAS
    $compras = \App\Models\DocumentoCompra::where('fecha_vencimiento', '<', $hoy)
        ->get()
        ->filter(fn($doc) => $doc->saldo_pendiente > 0)
        ->sortBy('fecha_vencimiento');

    if ($ventas->isEmpty() && $compras->isEmpty()) {
        // Log::info(' [ATRASADOS] No hay documentos vencidos con saldo pendiente.');
        return;
    }

    $destinos = [
        'eliascorrea@4nlogistica.cl',
        'NataliaLeyton@4nlogistica.cl',
        'hansdelabarra@4nlogistica.cl',
        'marcelo@4nlogistica.cl',

    ];



    Mail::to($destinos)->send(new DocumentosAtrasadosMail($ventas, $compras));

    // Log::info(' [ATRASADOS] Correo de documentos vencidos enviado a (' . implode(', ', $destinos) . ')');
})
->cron('0 7 * * 1,5');


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
    SCL ARICA         10    NN    CARGA GENERAL
    SCL IQUIQUE       10    NN    CARGA GENERAL
    SCL ANTOFAGASTA   10    NN    CARGA GENERAL
    SCL CALAMA        10    NN    CARGA GENERAL
    SCL PUNTA ARENAS  10    NN    CARGA GENERAL
    SCL BALMACEDA     10    NN    CARGA GENERAL
    ";

    Mail::to([
        'carganacional.chile@latam.com',
        'operaciones@4nlogistica.cl',
        'gisseth.mondaca@latam.com',
        'hansdelabarra@4nlogistica.cl',
        'homeropardo@4nlogistica.cl',
        'eliascorrea@4nlogistica.cl',
        'jp.soza@4nlogistica.cl',
    ])
    ->send(new ReservasDiariasMail($mensaje, $mensaje_tabular));


    // Log::info("Correo de ReservasDiarias enviado a las 8:00 (Lun-Vie)");

})
->weekdays()->at('08:00');


Schedule::call(function () {

    Log::info('⏳ [VACACIONES] Iniciando verificación de vacaciones próximas');

    $hoy = Carbon::today();

    $vacaciones = Vacacion::with(['trabajador', 'solicitud'])
        ->whereHas('solicitud', function ($query) {
            $query->where('estado', 'aprobado')
                  ->where('tipo_dia', 'vacaciones');
        })
        ->whereDate('fecha_inicio', '>', $hoy)
        ->get();

    foreach ($vacaciones as $vacacion) {

        $fechaInicio = Carbon::parse($vacacion->fecha_inicio);
        $diasRestantes = $hoy->diffInDays($fechaInicio, false);

        // Ventana válida: desde 7 días antes hasta 1 día antes
        if ($diasRestantes > 7 || $diasRestantes <= 0) {
            continue;
        }

        // Lógica intercalada (día por medio)
        if ($diasRestantes % 2 === 0) {
            continue;
        }

        Log::info(
            '[VACACIONES] Vacación próxima detectada',
            [
                'vacacion_id'    => $vacacion->id,
                'trabajador_id'  => $vacacion->trabajador_id,
                'fecha_inicio'   => $vacacion->fecha_inicio->format('Y-m-d'),
                'dias_restantes' => $diasRestantes,
            ]
        );

        $destinos = [
            'luisdelabarra@4nlogistica.cl',
            'raul.suazo@4nlogistica.cl',
            'jp.soza@4nlogistica.cl',
            'hansdelabarra@4nlogistica.cl',
            'eliascorrea@4nlogistica.cl',

        ];

        Mail::to($destinos)->send(
            new VacacionesProximasNotificacion($vacacion, $diasRestantes)
        );

        Log::info(
            '[VACACIONES] Correo enviado por vacaciones próximas',
            [
                'vacacion_id'    => $vacacion->id,
                'fecha_inicio'   => $vacacion->fecha_inicio->format('Y-m-d'),
                'dias_restantes' => $diasRestantes,
                'destinatarios'  => $destinos,
            ]
        );
    }

    Log::info('[VACACIONES] Verificación de vacaciones finalizada');

})->dailyAt('09:00');





Schedule::call(function () {
    app(AutomaticEmailService::class)->procesar();
})->everyMinute();


Schedule::command('backup:database')
    ->dailyAt('23:00')
    ->timezone('America/Santiago');



Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();



// ->everyMinute();