<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\VacacionController;
use App\Http\Controllers\HistorialVacacionController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProveedorImportController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\LabelController;
use Illuminate\Http\Request;
use App\Http\Controllers\AsistenciaController;
use App\Models\User;
use App\Http\Controllers\SolicitudManualController;
use App\Http\Controllers\BultoController;
use App\Http\Controllers\AreaController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ReclamoController;
use App\Http\Controllers\ComunaController;
use App\Http\Controllers\TrackingDashboardController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\ExportacionController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ManifiestoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\CotizadorController;
use App\Http\Controllers\CobranzaController;
use App\Http\Controllers\CobranzaCompraController;
use App\Http\Controllers\DocumentoFinancieroController;
use App\Http\Controllers\AbonoController;
use App\Http\Controllers\TrackingProductoController;
use App\Http\Controllers\CruceController;
use App\Http\Controllers\DocumentoCompraController;
use App\Http\Controllers\Admin\BackupDatabaseController;
use App\Http\Controllers\TrackingDeliveryLinksController;
use App\Http\Controllers\HonorarioResumenAnualController;
use App\Http\Controllers\HonorarioMensualRecController;
use App\Http\Controllers\TrackingReportController;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        // Si el usuario tiene el rol 'admin' o 'jefe'
        if ($user->roles->contains('name', 'admin') || $user->roles->contains('name', 'jefe')) {
            return redirect('/empleados'); // Redirige a la página principal para administradores y jefes
        }

        // Si el usuario no tiene rol asignado, se asume que es un empleado
        return redirect('/empleados/perfil'); // Redirige al perfil del empleado
    }

    return view('landing'); // Página para usuarios no autenticados
});





// 2. Rutas de autenticación
Auth::routes();

// 3. Rutas de perfil de empleados
route::get('/empleados/perfil', [PerfilController::class, 'show'])->middleware('auth')->name('empleados.perfil'); //Muestra el Perfil completo del empleado
route::get('/perfiles/{trabajador}/editar', [PerfilController::class, 'edit'])->middleware('auth')->name('perfiles.editar'); //Ruta para poder editar algunos campos del perfil del empleado
route::put('/empleados/perfil', [PerfilController::class, 'update'])->middleware('auth')->name('empleados.perfil.update'); //Valida los datos que se están editando

route::get('/empleados/solicitudes', [PerfilController::class, 'verSolicitudes'])->name('perfiles.solicitudes');//Muestra todas las solicitudes que a echo el empleado ya sea Cambios|Vacaciones



// Ruta para ver el formulario de cambio de contraseña
Route::get('/perfil/cambiar_contraseña', [PerfilController::class, 'showChangePasswordForm'])->middleware('auth')->name('perfiles.cambiar_contraseña');
// Ruta para procesar el cambio de contraseña
Route::put('/perfil/cambiar_contraseña', [PerfilController::class, 'changePassword'])->middleware('auth')->name('perfiles.cambiar_contraseña.update');
Route::get('/perfil/reclamos-area', [PerfilController::class, 'verReclamosArea'])->name('perfiles.reclamos.area');



// 4. Rutas para solicitudes
route::get('/solicitudes/create', [SolicitudController::class, 'create'])->middleware('auth')->name('solicitudes.create'); // Vista del empleado, para pedir solicitud de modificación
route::post('/solicitudes', [SolicitudController::class, 'store'])->middleware('auth')->name('solicitudes.store'); //Valida los campos que se piden cuando se solicita una modificación
route::get('/solicitudes', [SolicitudController::class, 'index'])->middleware('auth')->name('solicitudes.index'); //Muestra las solicitudes de cambio pero en la vista del Administrador
Route::post('/solicitudes/{id}/aprobar', [SolicitudController::class, 'approve'])->middleware('auth')->name('solicitudes.approve');//Botón que aprueba una solicitud
Route::post('/solicitudes/{id}/rechazar', [SolicitudController::class, 'reject'])->middleware('auth')->name('solicitudes.reject');//Botón que rechaza una solicitud
route::get('/solicitudes/vacaciones', [SolicitudController::class, 'vacaciones'])->name('solicitudes.vacaciones');//Muestra las solicitudes de las Vacaciones por el lado del Administrador
route::get('/solicitudes/{id}/descargar', [SolicitudController::class, 'descargarArchivo'])->name('solicitudes.descargar');//Enlace para descargar el archivo que adjunto el empleado para pedir una solicitud

Route::get('solicitudes/{id}/descargar-archivo-admin', [SolicitudController::class, 'descargaArchivoAEmpleado'])->name('solicitudes.descargar-archivo-admin');//Permite que el empleado descargue el archivo que el administrador adjuntó como respuesta.


// 5. Rutas de notificaciones
route::post('/notifications/mark-all-read', function () {
    Auth::user()->unreadNotifications->markAsRead();
    return back();
})->name('notifications.markAllAsRead');

//agregar una ruta para consultar notificaciones
// Route::get('/notificaciones/recientes', function () {
//     // Mantener vivos los flashes para el siguiente request
//     session()->reflash();

//     // Log de verificación temporal
//     Log::info('Reflash ejecutado en /notificaciones/recientes', [
//         'session_flash' => session()->get('_flash'),
//         'session_keys'  => array_keys(session()->all()),
//     ]);

//     $notificaciones = Auth::user()->unreadNotifications
//         ->sortByDesc('created_at')
//         ->take(5)
//         ->map(function ($n) {
//             return [
//                 'id'      => $n->id,
//                 'mensaje' => $n->data['mensaje'],
//                 'link'    => $n->data['link'] . '?notificacion_id=' . $n->id,
//                 'tipo'    => $n->type,
//             ];
//         });

//     return response()->json([
//         'total' => $notificaciones->count(),
//         'items' => $notificaciones->values()
//     ]);
// })->middleware('auth');




Route::get('/notificaciones/empleado', function () {
    $tiposPermitidos = [
        'App\Notifications\SolicitudActualizada',
        'App\Notifications\NuevoReclamoAreaNotification',
        'App\Notifications\ReclamoRespondidoNotification',
        'App\Notifications\NuevoComentarioReclamoNotification',
        'App\Notifications\ReclamoCerradoNotification',
    ];

    $notificaciones = Auth::user()->unreadNotifications
        ->whereIn('type', $tiposPermitidos)
        ->sortByDesc('created_at')
        ->take(5)
        ->map(function ($n) {
            return [
                'id' => $n->id,
                'mensaje' => $n->data['mensaje'],
                'link' => $n->data['link'] . '?notificacion_id=' . $n->id,
                'tipo' => $n->type,
            ];
        });

    return response()->json([
        'total' => $notificaciones->count(),
        'items' => $notificaciones->values()
    ]);
})->middleware('auth');


Route::get('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');


Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');


// PANEL ADMIN
Route::middleware(['auth'])->group(function () {

    Route::get('/admin/control-panel', 
        [\App\Http\Controllers\Admin\ControlPanelAdminController::class, 'index']
    )->name('admin.controlpanel.index');

    Route::resource('automatic-emails', 
        \App\Http\Controllers\Admin\AutomaticEmailController::class
    )->names('admin.automatic_emails');

    Route::post(
        'automatic-emails/{automatic_email}/test',
        [\App\Http\Controllers\Admin\AutomaticEmailController::class, 'test']
    )->name('admin.automatic_emails.test');


    Route::post(
        'automatic-emails/{automatic_email}/simulate',
        [\App\Http\Controllers\Admin\AutomaticEmailController::class, 'simulate']
    )->name('admin.automatic_emails.simulate');



    

});




// ROLES

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/roles', [\App\Http\Controllers\Admin\RoleManagerController::class, 'index'])->name('admin.roles.index');
    Route::post('/admin/roles/{user}/assign', [\App\Http\Controllers\Admin\RoleManagerController::class, 'assign'])->name('admin.roles.assign');
});

// BackupDatabase
Route::get('/admin/backup', [BackupDatabaseController::class, 'index'])
    ->middleware(['auth'])
    ->name('admin.backup.index');

Route::post('/admin/backup-database', [BackupDatabaseController::class, 'export'])
    ->middleware(['auth'])
    ->name('admin.backup.database');



// 6. Rutas para vacaciones
route::get('/vacaciones/create', [VacacionController::class, 'create'])->name('vacaciones.create');// Vista del empleado, para pedir solicitud de vacación
route::post('/vacaciones', [VacacionController::class, 'store'])->name('vacaciones.store');//Valida los campos que se piden cuando se solicitan días

route::get('vacaciones/descargar/{id}', [VacacionController::class, 'descargarArchivo'])->name('vacaciones.descargar');//permitiendo al administrador descargar el archivo adjunto de la solicitud de vacaciones.

Route::post('/solicitudes/vacaciones/{id}/aprobar', [SolicitudController::class, 'approveVacacion'])->middleware('auth')->name('solicitudes.vacaciones.approve');//Botón que aprueba solicitud de VACACIONES
Route::post('/solicitudes/vacaciones/{id}/rechazar', [SolicitudController::class, 'rejectVacacion'])->middleware('auth')->name('solicitudes.vacaciones.reject');//Botón que RECHAZA solicitud de VACACIONES
Route::get('/vacaciones/{id}/descargar-archivo-admin', [VacacionController::class, 'descargarArchivoAdmin'])->name('vacaciones.descargarArchivoAdmin'); //Esta ruta específica se encargará de manejar la descarga del archivo PDF que se crea de manera automática
Route::get('vacaciones/exportar-disponibles', [VacacionController::class, 'exportarDisponibilidad'])
    ->name('vacaciones.exportarDisponibles');


//ruta para que el empleado pueda acceder a la descarga del archivo que adjunto el administrador cuando aprueba o rechaza una solicitud
Route::get('/vacaciones/{id}/descargar-archivo-respuesta', [VacacionController::class, 'descargarArchivoRespuestaAdmin'])->middleware('auth')->name('vacaciones.descargarArchivoRespuestaAdmin');

//ruta en web.php para que el administrador pueda acceder a la vista centralizada de archivos:
Route::get('/admin/archivos-respaldo', [VacacionController::class, 'mostrarArchivosRespaldo'])
    ->name('admin.archivos-respaldo');

Route::get('/mis-vacaciones', [App\Http\Controllers\VacacionController::class, 'misVacaciones'])
    ->name('vacaciones.mis');


// Ruta para exportar Excel el listado de los empleados
Route::get('/empleados/exportExcel', [TrabajadorController::class, 'exportExcel'])->name('empleados.exportExcel');

//Ruta para exportar en Excel el listado de las facturas
Route::get('/facturas/export', [FacturaController::class, 'export'])->name('facturas.export');

// OPERADORES
Route::get('/clasificacion-operativa/exportar', [\App\Http\Controllers\ClasificacionOperativaController::class, 'exportar'])->name('clasificacion-operativa.exportar');

Route::resource('clasificacion-operativa', \App\Http\Controllers\ClasificacionOperativaController::class)
    ->only(['index', 'edit', 'update']) // limitar solo a lo que usas (opcional)
    ->middleware('auth');



///PanelFinanza//////Historial de Movimientos Ventas
Route::get('/panelfinanza/show', [App\Http\Controllers\PanelFinanzaController::class, 'show'])
    ->name('panelfinanza.show');

/// PanelFinanza /// Historial de Movimientos Compras
Route::get('/panelfinanza/show-compras', [App\Http\Controllers\PanelFinanzaController::class, 'showCompras'])
    ->name('panelfinanza.show_compras');


Route::get('/panelfinanza/export', [App\Http\Controllers\PanelFinanzaController::class, 'export'])
    ->name('panelfinanza.export');

Route::get('/panelfinanza/export-compras', [App\Http\Controllers\PanelFinanzaController::class, 'exportCompras'])
    ->name('panelfinanza.export_compras');



// 7. Rutas para otros modelos
Route::resource('empresas', '\App\Http\Controllers\EmpresaController')->middleware('auth');
Route::resource('cargos', '\App\Http\Controllers\CargoController')->middleware('auth');
Route::resource('afps', 'App\Http\Controllers\AFPController')->middleware('auth');


// Ruta para módulo de Bancos
Route::resource('bancos', 'App\Http\Controllers\BancoController')->middleware('auth');


// Ruta para módulo Centro Costo
Route::resource('centro_costos', 'App\Http\Controllers\Centro_CostoController')->middleware('auth');


// Ruta para módulo Tipo Cuenta
Route::resource('tipo_cuentas', 'App\Http\Controllers\TipoCuentaController')->middleware('auth');

// Ruta para módulo Tipo Documento
Route::resource('tipo_documentos', 'App\Http\Controllers\TipoDocumentoController')->middleware('auth');

// Ruta para módulo Forma Pago
Route::resource('forma_pagos', 'App\Http\Controllers\FormaPagoController')->middleware('auth');


Route::get('/comunas/export', [ComunaController::class, 'export'])->name('comunas.export');
Route::resource('comunas', 'App\Http\Controllers\ComunaController')->middleware('auth');


route::get('/empleados/localidades', [TrabajadorController::class, 'mostrarLocalidad'])->name('empleados.localidades');
Route::resource('saluds', 'App\Http\Controllers\SaludController')->middleware('auth');
Route::resource('situacions', 'App\Http\Controllers\SituacionController')->middleware('auth');
Route::resource('estado_civil', 'App\Http\Controllers\EstadoCivilController')->middleware('auth');
Route::resource('turnos', 'App\Http\Controllers\TurnoController')->middleware('auth');
Route::resource('sistema_trabajos', '\App\Http\Controllers\SistemaTrabajoController')->middleware('auth');
Route::resource('empleados', '\App\Http\Controllers\TrabajadorController')->middleware('auth');
Route::resource('regions', 'App\Http\Controllers\RegionController')->middleware('auth');
Route::resource('tipo_vestimentas', 'App\Http\Controllers\TipoVestimentaController')->middleware('auth');
Route::resource('tallas', 'App\Http\Controllers\TallaController')->middleware('auth');
Route::resource('hijos', 'App\Http\Controllers\HijoController')->middleware('auth');
Route::resource('equipos', EquipoController::class);




/////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////RCV VENTAS////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////

// Importar archivo Excel
Route::post('/cobranzas/documentos/import', [DocumentoFinancieroController::class, 'import'])
    ->name('cobranzas.import');
// Mostrar documentos financieros
Route::get('/cobranzas/documentos', [DocumentoFinancieroController::class, 'index'])
    ->name('cobranzas.documentos');

Route::patch('/documentos/{documento}/status', [DocumentoFinancieroController::class, 'updateStatus'])
    ->name('documentos.updateStatus');

Route::get('documentos/export', [DocumentoFinancieroController::class, 'export'])
    ->name('documentos.export');

Route::get('/finanzas/export-all', [DocumentoFinancieroController::class, 'exportAll'])->name('finanzas.exportAll');


// Mostrar formulario de edición de un documento financiero
Route::get('/cobranzas/documentos/{documento}/edit', [DocumentoFinancieroController::class, 'edit'])
    ->name('cobranzas.documentos.edit');

// Actualizar un documento financiero
Route::put('/cobranzas/documentos/{documento}', [DocumentoFinancieroController::class, 'update'])
    ->name('cobranzas.documentos.update');


Route::get('/documentos/{documento}/detalles', [App\Http\Controllers\DocumentoFinancieroController::class, 'show'])
    ->name('documentos.detalles');


Route::post('/documentos/{documento}/abonos', [DocumentoFinancieroController::class, 'storeAbono'])
    ->name('documentos.abonos.store');    

//Registrar un nuevo cruce
Route::post('/documentos/{documento}/cruces', [DocumentoFinancieroController::class, 'storeCruce'])
    ->name('documentos.cruces.store');


Route::get('/cobranzas/general', [App\Http\Controllers\DocumentoFinancieroController::class, 'general'])
        ->name('cobranzas.general');



Route::get('/cobranzas/documentos/column-filter', [DocumentoFinancieroController::class, 'filtrarColumnas'])
    ->name('cobranzas.column_filter');


///////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// Cobranzas ////////////////////////////////////////////// 
///////////////////////////////////////////////////////////////////////////////////////////    

Route::get('/cobranzas/export', [CobranzaController::class, 'export'])->name('cobranzas.export');
Route::post('/cobranzas/reprocesar-pendientes', [CobranzaController::class, 'reprocesarPendientes'])
     ->name('cobranzas.reprocesar-pendientes');
     
Route::post('cobranzas/reprocesar-pendientes-compras', [CobranzaController::class, 'reprocesarPendientesCompras'])
    ->name('cobranzas.reprocesar-pendientes-compras');

Route::resource('cobranzas', CobranzaController::class);

///////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////// COBRANZA COMPRA ///////////////////////////////////////////////



Route::get('/cobranzas/export/compras', [CobranzaCompraController::class, 'export'])->name('cobranzasCompra.export');


Route::post('/cobranzas-compras/reprocesar-pendientes-compras', [CobranzaCompraController::class, 'reprocesarPendientesCompras'])
    ->name('cobranzas-compras.reprocesar-pendientes-compras');

Route::resource('cobranzas-compras', CobranzaCompraController::class)
    ->parameters(['cobranzas-compras' => 'cobranzaCompra']);




/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////

// Cotizadores
Route::resource('cotizadores', CotizadorController::class);

Route::post('/cotizadores/calcular-distancia', [CotizadorController::class, 'calcularDistancia'])
    ->name('cotizadores.calcular-distancia');

// abonos
// Mostrar todos los abonos de un documento
Route::get('/documentos/{documento}/abonos', [AbonoController::class, 'index'])
    ->name('abonos.index');

// Mostrar formulario para editar un abono
Route::get('/abonos/{id}/edit', [AbonoController::class, 'edit'])
    ->name('abonos.edit');

// Actualizar un abono existente
Route::put('/abonos/{id}', [AbonoController::class, 'update'])
    ->name('abonos.update');

// Eliminar un abono
Route::delete('/abonos/{id}', [AbonoController::class, 'destroy'])
    ->name('abonos.destroy');

Route::get('/abonos/show', [AbonoController::class, 'show'])->name('abonos.show');



/////CRUCES
// CRUD de cruces
Route::get('/documentos/{documento}/cruces', [CruceController::class, 'index'])->name('cruces.index');
Route::get('/cruces/{id}/edit', [CruceController::class, 'edit'])->name('cruces.edit');
Route::put('/cruces/{id}', [CruceController::class, 'update'])->name('cruces.update');
Route::delete('/cruces/{id}', [CruceController::class, 'destroy'])->name('cruces.destroy');
Route::get('/cruces/show', [App\Http\Controllers\CruceController::class, 'show'])
    ->name('cruces.show');




//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////    

// PAGOS

// Registrar un pago directamente desde PagoDocumentoController
Route::post('/documentos/{documento}/pagos', [App\Http\Controllers\PagoDocumentoController::class, 'store'])
    ->name('documentos.pagos.store');

//Ruta para registrar pagos masivos
Route::post('/documentos/pagos/masivo', [App\Http\Controllers\PagoDocumentoController::class, 'storeMasivo'])
    ->name('documentos.pagos.masivo');

//Buscar documentos pendientes para pagos masivos
Route::get('/api/documentos/buscar', [App\Http\Controllers\PagoDocumentoController::class, 'buscarDocumentos'])
    ->name('documentos.pagos.buscar');


// Eliminar un pago
Route::delete('/pagos/{id}', [App\Http\Controllers\PagoDocumentoController::class, 'destroy'])
    ->name('pagos.destroy');

Route::get(
    '/documentos/pagos/masivo/export',
    [App\Http\Controllers\PagoDocumentoController::class, 'exportPagosMasivos']
)->name('documentos.pagos.masivo.export');

Route::get(
    'documentos/pagos/masivo/empresa/{token}',
    [App\Http\Controllers\PagoDocumentoController::class, 'downloadPagosMasivosEmpresa']
)->name('documentos.pagos.masivo.empresa.descargar');








Route::get('/labels', [LabelController::class, 'panel']);
Route::match(['get', 'post'], '/labels/excel', [LabelController::class, 'uploadExcel']);
Route::match(['get', 'post'], '/labels/grande', [LabelController::class, 'uploadEtiquetaGrande']);
Route::get('/labels/excel/template', [LabelController::class, 'downloadTemplate']);





//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 

//ProntoPAGO///////////////////////////////////////// 
// Registrar un pronto pago
Route::post('/prontopagos/{documento}', [App\Http\Controllers\ProntoPagoController::class, 'store'])
    ->name('prontopagos.store');

// Eliminar un pronto pago
Route::delete('/prontopagos/{id}', [App\Http\Controllers\ProntoPagoController::class, 'destroy'])
    ->name('prontopagos.destroy');




//////////////////////////////// 
// cobranzas/finanzas_compras
Route::get('/finanzas/compras/filtrar-columnas', [App\Http\Controllers\DocumentoCompraController::class, 'filtrar'])
    ->name('finanzas_compras.column_filter');
    
Route::get('/finanzas/compras', [DocumentoCompraController::class, 'index'])->name('finanzas_compras.index');
Route::post('/finanzas/compras/import', [DocumentoCompraController::class, 'import'])->name('finanzas_compras.import');
Route::get('/finanzas_compras/export', [DocumentoCompraController::class, 'export'])->name('finanzas_compras.export');
Route::get('/finanzas-compras/export-all', [DocumentoCompraController::class, 'exportAll'])
    ->name('finanzas_compras.exportAll');

//Actualizar estado de un documento de compra
Route::patch('/finanzas/compras/{id}/estado', [DocumentoCompraController::class, 'updateEstado'])
    ->name('finanzas_compras.updateEstado');

//Registrar Abono manual
Route::post('/finanzas/compras/{documento}/abono', [DocumentoCompraController::class, 'storeAbono'])
    ->name('finanzas_compras.abonos.store');

//Registrar Cruce manual
Route::post('/finanzas/compras/{documento}/cruce', [DocumentoCompraController::class, 'storeCruce'])
    ->name('finanzas_compras.cruces.store');

Route::get('/finanzas/compras/{documento}', [DocumentoCompraController::class, 'show'])
    ->name('finanzas_compras.show');

Route::get('/finanzas/compras/sugerencias', [DocumentoCompraController::class, 'sugerencias'])
    ->name('finanzas_compras.sugerencias');

Route::get('/compras/limpiar-sugerencias', function () {
    session()->forget('sugerencias_notas_compras');
    return response()->json(['ok' => true]);
})->name('compras.limpiar_sugerencias');

Route::post('/compras/asignar-referencia', [DocumentoCompraController::class, 'asignarReferencia'])
    ->name('compras.asignar_referencia');
Route::post('/compras/asignar-referencias', [DocumentoCompraController::class, 'asignarReferencias'])
    ->name('compras.asignar_referencias');






// Rutas Honorarios Resumen Anual
Route::get(
    '/honorarios/resumen-anual',
    [HonorarioResumenAnualController::class, 'index']
)->name('honorarios.resumen.index');

Route::post(
    '/honorarios/resumen-anual/store',
    [HonorarioResumenAnualController::class, 'store']
)->name('honorarios.resumen.store');


Route::post(
    '/honorarios/resumen-anual/import',
    [HonorarioResumenAnualController::class, 'import']
)->name('honorarios.resumen.import');



// Rutas Honorarios Mensual Recepción

// Exportar

Route::get(
    'honorarios/mensual-rec/export',
    [HonorarioMensualRecController::class, 'export']
)->name('honorarios.mensual.export');

Route::get(
    '/honorarios/mensual-rec',
    [HonorarioMensualRecController::class, 'index']
)->name('honorarios.mensual.index');


Route::post(
    '/honorarios/mensual-rec/import',
    [HonorarioMensualRecController::class, 'import']
)->name('honorarios.mensual.import');

Route::post(
    '/honorarios/mensual-rec/store',
    [HonorarioMensualRecController::class, 'store']
)->name('honorarios.mensual.store');

Route::get(
    '/honorarios/panel',
    [HonorarioMensualRecController::class, 'panel']
)->name('honorarios.mensual.panel');


Route::get(
        '/boleta-mensual/panel',
        [HonorarioMensualRecController::class, 'panel']
    )->name('boleta.mensual.panel');


// Detalle Honorarios Mensuales Recibidos (por empresa y periodo)
Route::get(
    '/honorarios/mensual-rec/detalle/{empresa}/{anio}/{mes}',
    [HonorarioMensualRecController::class, 'detalle']
)->name('honorarios.mensual.detalle');

Route::post(
    '/honorarios/mensual-rec/{honorario}/abono',
    [HonorarioMensualRecController::class, 'storeAbono']
)->name('honorarios.mensual.abono.store');

Route::post(
    '/honorarios/mensual-rec/{honorario}/cruce',
    [HonorarioMensualRecController::class, 'storeCruce']
)->name('honorarios.mensual.cruce.store');

Route::post(
    '/honorarios/mensual-rec/{honorario}/pago',
    [HonorarioMensualRecController::class, 'storePago']
)->name('honorarios.mensual.pago.store');


Route::post(
    'honorarios/mensual/pago-masivo',
    [HonorarioMensualRecController::class, 'storePagoMasivo']
)->name('honorarios.mensual.pago.masivo');


Route::post(
    'honorarios/mensual/pago-masivo/exportar',
    [HonorarioMensualRecController::class, 'storePagoMasivoExport']
)->name('honorarios.mensual.pago.masivo.exportar');

Route::get(
    'honorarios/mensual/pago-masivo/descargar/{token}',
    [HonorarioMensualRecController::class, 'downloadPagoMasivoExcel']
)->name('honorarios.mensual.pago.masivo.descargar');





Route::get(
    'honorarios/mensual/buscar',
    [HonorarioMensualRecController::class, 'buscarHonorarios']
)->name('honorarios.mensual.buscar');


Route::post(
    '/honorarios/mensual-rec/{honorario}/pronto-pago',
    [HonorarioMensualRecController::class, 'storeProntoPago']
)->name('honorarios.mensual.prontopago.store');

Route::post(
    '/honorarios/mensual-rec/estado',
    [HonorarioMensualRecController::class, 'storeEstado']
)->name('honorarios.mensual.estado.store');

Route::get(
    'honorarios/mensual-rec/{honorario}',
    [\App\Http\Controllers\HonorarioMensualRecController::class, 'show']
)->name('honorarios.mensual.show');

// Revetir cambios y eliminar estados manuales
Route::delete(
    '/honorarios/mensual-rec/abonos/{abonoId}/revertir',
    [HonorarioMensualRecController::class, 'revertirAbono']
)->name('honorarios.mensual.abono.revertir');

Route::delete(
    '/honorarios/mensual-rec/cruces/{cruceId}/revertir',
    [HonorarioMensualRecController::class, 'revertirCruce']
)->name('honorarios.mensual.cruce.revertir');

Route::delete(
    '/honorarios/mensual-rec/pagos/{pagoId}/revertir',
    [HonorarioMensualRecController::class, 'revertirPago']
)->name('honorarios.mensual.pago.revertir');

Route::delete(
    '/honorarios/mensual-rec/pronto-pagos/{prontoPagoId}/revertir',
    [HonorarioMensualRecController::class, 'revertirProntoPago']
)->name('honorarios.mensual.prontopago.revertir');

// Actualizar servicio cuado es otro
Route::patch(
    '/honorarios-mensuales/{honorario}/servicio',
    [HonorarioMensualRecController::class, 'updateServicio']
)->name('honorarios.mensual.servicio.update');





// Historial de cambios en honorarios mensuales recibidos
use App\Http\Controllers\MovimientoHonorarioMensualRecController;

Route::get(
    '/movimientos-honorarios-mensuales-rec/export',
    [MovimientoHonorarioMensualRecController::class, 'export']
)->name('movimientos.honorarios.export');


Route::get(
    'movimientos-honorarios-mensuales-rec/historial',
    [MovimientoHonorarioMensualRecController::class, 'historial']
)->name('movimientos.honorarios.historial');





// rEPORTE TRACKING 

Route::get('/report/tracking/{year}/{month}', [TrackingReportController::class, 'monthly']);



// Ruta para desvincular un empleado
Route::post('/empleados/{id}/desvincular', [App\Http\Controllers\TrabajadorController::class, 'desvincular'])->name('empleados.desvincular');


// Descargar archivo PDF del contrato
Route::get('contratos/{id}/download', [ContratoController::class, 'download'])
    ->name('contratos.download')
    ->middleware('auth');

// Mostrar formulario para registrar contrato (recibe ID del trabajador)
Route::get('contratos/create/{trabajador}', [ContratoController::class, 'create'])
    ->name('contratos.create')
    ->middleware('auth');

// Guardar contrato para trabajador (POST con ID del trabajador)
Route::post('contratos/{trabajador}', [ContratoController::class, 'store'])
    ->name('contratos.store')
    ->middleware('auth');

Route::get('contratos/{contrato}/edit', [ContratoController::class, 'edit'])
    ->name('contratos.edit')
    ->middleware('auth');

Route::put('contratos/{contrato}', [ContratoController::class, 'update'])
    ->name('contratos.update')
    ->middleware('auth');


// Otras rutas de tipo resource (index y destroy)
Route::resource('contratos', ContratoController::class)
    ->only(['index', 'destroy'])
    ->middleware('auth');


Route::prefix('exportar')->group(function () {
    Route::get('/bancos', [ExportacionController::class, 'exportarBancos'])->name('exportar.bancos');
    Route::get('/tipo-cuentas', [ExportacionController::class, 'exportarTipoCuentas'])->name('exportar.tipo_cuentas');
    Route::get('/tipo-documentos', [ExportacionController::class, 'exportarTipoDocumentos'])->name('exportar.tipo_documentos');

    Route::get('/centro-costos', [ExportacionController::class, 'exportarCentroCosto'])->name('exportar.centro_costo');
    Route::get('/formas-pago', [ExportacionController::class, 'exportarFormaPago'])->name('exportar.forma_pago');
    Route::get('/plazo-pago', [ExportacionController::class, 'exportarPlazoPago'])->name('exportar.plazo_pago');

    Route::get('/comunas', [ExportacionController::class, 'exportarEmpresa'])->name('exportar.empresas');


});

// Obtener link foto en consulta de Tracking
Route::get('/tracking/delivery-links', [TrackingDeliveryLinksController::class, 'index']);
Route::post('/reports/tracking/search', [TrackingDeliveryLinksController::class, 'search']);
Route::post('/reports/tracking/export', [TrackingDeliveryLinksController::class, 'export']);
Route::post('/reports/tracking/search-batch', [TrackingDeliveryLinksController::class, 'searchBatch']);
Route::post('/reports/tracking/export-batch', [TrackingDeliveryLinksController::class, 'exportBatch']);






//////////////////// Tracking /////////////
Route::resource('escaneo', 'App\Http\Controllers\ProductoEscaneadoController')->middleware('auth');
Route::get('/tracking-productos', [TrackingProductoController::class, 'index'])->name('tracking_productos.index');
Route::get('/tracking-productos/retiro', [TrackingProductoController::class, 'retiro'])->name('tracking_productos.retiro');
Route::post('/tracking-productos/retiro', [TrackingProductoController::class, 'guardarRetiro'])->name('tracking_productos.guardar_retiro');
Route::post('/tracking-productos/retiro/agregar', [TrackingProductoController::class, 'agregarCodigo'])->name('tracking_productos.agregar_codigo');
Route::post('/tracking-productos/retiro/finalizar', [TrackingProductoController::class, 'guardarLoteRetiro'])->name('tracking_productos.guardar_lote');


Route::get('/tracking-productos/recepcion', [TrackingProductoController::class, 'recepcion'])->name('tracking_productos.recepcion');
Route::post('/tracking-productos/recepcion/agregar', [TrackingProductoController::class, 'agregarCodigoRecepcion'])->name('tracking_productos.agregar_codigo_recepcion');
Route::post('/tracking-productos/recepcion/finalizar', [TrackingProductoController::class, 'guardarRecepcion'])->name('tracking_productos.guardar_recepcion');


Route::get('/tracking/en-ruta', [TrackingProductoController::class, 'enRuta'])->name('tracking_productos.en_ruta');
Route::post('/tracking/en-ruta/agregar', [TrackingProductoController::class, 'agregarCodigoRuta'])->name('tracking_productos.agregar_codigo_ruta');
Route::post('/tracking/en-ruta/guardar', [TrackingProductoController::class, 'guardarRuta'])->name('tracking_productos.guardar_ruta');

Route::post('/tracking-productos/asignar-chofer', [TrackingProductoController::class, 'asignarChofer'])->name('tracking_productos.asignar_chofer');
Route::get('/tracking-productos/asignar-individual', [TrackingProductoController::class, 'asignarIndividual'])->name('tracking_productos.asignar_individual');
Route::post('/tracking-productos/asignar-individual', [TrackingProductoController::class, 'asignarSeleccionados'])->name('tracking_productos.asignar_seleccionados');
Route::post('/tracking-productos/asignar/agregar-codigo', [TrackingProductoController::class, 'agregarCodigoAsignacion'])->name('tracking_productos.agregar_codigo_asignacion');

Route::get('/tracking/dashboard', [TrackingDashboardController::class, 'index'])->name('tracking.dashboard');



// Áreas
Route::post('/areas/{area}/asignar', [AreaController::class, 'asignar'])->name('areas.asignar');
Route::post('/areas/{area}/quitar-trabajador/{trabajador}', [AreaController::class, 'quitar'])->name('areas.quitar');

Route::post('/areas/{area}/asignar-secundaria', [AreaController::class, 'asignarSecundaria'])->name('areas.asignarSecundaria');
Route::post('/areas/{area}/quitar-secundaria/{trabajador}', [AreaController::class, 'quitarSecundaria'])->name('areas.quitarSecundaria');


Route::resource('areas', 'App\Http\Controllers\AreaController')->middleware('auth');



// Ruta para las compras
// Rutas estáticas primero
Route::get('/compras/exportar-proveedores-faltantes', [CompraController::class, 'exportarProveedoresFaltantes'])
    ->name('compras.exportarProveedoresFaltantes');
Route::post('/compras/importar', [CompraController::class, 'importar'])->name('compras.importar');
Route::post('/compras/confirmar-importacion', [CompraController::class, 'confirmarImportacion'])->name('compras.confirmarImportacion');
Route::get('/compras/descargar-plantilla', [CompraController::class, 'descargarPlantilla'])->name('compras.plantilla');
Route::get('/compras/exportar', [CompraController::class, 'export'])->name('compras.exportar');

// Rutas con parámetros después
Route::get('/compras/{id}/archivo-oc', [CompraController::class, 'descargarArchivoOC'])->name('compras.descargarArchivoOC');
Route::get('/compras/{id}/archivo-documento', [CompraController::class, 'descargarArchivoDocumento'])->name('compras.descargarArchivoDocumento');
Route::patch('/compras/{id}/status', [CompraController::class, 'updateStatus'])->name('compras.updateStatus');

// Recurso al final
Route::resource('compras', CompraController::class);


// MANIFIESTO
Route::get('/manifiesto', [ManifiestoController::class, 'index'])->name('manifiesto.index');
Route::post('/manifiesto/upload', [ManifiestoController::class, 'upload'])->name('manifiesto.upload');
Route::post('/manifiesto/store', [ManifiestoController::class, 'store'])->name('manifiesto.store');
// Nueva ruta para procesar tabla pegada desde el correo
Route::post('/manifiesto/pegar', [ManifiestoController::class, 'paste'])->name('manifiesto.paste');
Route::get('/manifiestos/export', [ManifiestoController::class, 'export'])->name('manifiestos.export');
Route::get('/manifiestos/limpiar', [ManifiestoController::class, 'limpiar'])->name('manifiestos.limpiar');
Route::post('/manifiesto/confirmar-area', [ManifiestoController::class, 'confirmarArea'])->name('manifiesto.confirmar-area');



// Ruta Dashboard
// Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::resource('bultos', 'App\Http\Controllers\BultoController')->middleware('auth');
Route::post('/bultos/import', [BultoController::class, 'importExcel'])->name('bultos.import');


Route::get('/reclamos/dashboard', [\App\Http\Controllers\ReclamoDashboardController::class, 'index'])->name('reclamos.dashboard');
Route::get('/dashboard/reclamos/exportar', [\App\Http\Controllers\ReclamoDashboardController::class, 'exportarExcel'])->name('dashboard.reclamos.export');

Route::post('/reclamos/{id}/cerrar', [App\Http\Controllers\ReclamoController::class, 'cerrar'])->name('reclamos.cerrar');
Route::post('/reclamos/{id}/responder', [App\Http\Controllers\ReclamoController::class, 'responder'])->name('reclamos.responder');
Route::post('/reclamos/{id}/reabrir', [\App\Http\Controllers\ReclamoController::class, 'reabrir'])->name('reclamos.reabrir');
Route::post('/reclamos/consulta', [\App\Http\Controllers\ReclamoController::class, 'storeConsulta'])->name('reclamos.consulta.store');
Route::get('/reclamos/ver/{id}', [ReclamoController::class, 'verReclamo'])->name('reclamos.ver');
Route::get('/reclamos/mios', [ReclamoController::class, 'misReclamos'])->name('reclamos.mios');
Route::post('/reclamos/{id}/comentar', [ReclamoController::class, 'comentar'])->name('reclamos.comentar');
Route::resource('reclamos', 'App\Http\Controllers\ReclamoController')->middleware('auth');

// Ruta PDF MANUAL
Route::get('/rrhh/generar-pdf', [SolicitudManualController::class, 'formulario'])->name('rrhh.formulario');
Route::post('/rrhh/generar-pdf', [SolicitudManualController::class, 'generarPDF'])->name('rrhh.generar-pdf');

// Tutorial Videos
Route::get('/tutorial', function () {
    return view('tutorial');
})->name('tutorial');

//Ruta para los proveedores
// Ruta para descargar la plantilla vacía de proveedores
Route::get('/proveedores/descargar-plantilla', [ProveedorController::class, 'descargarPlantilla'])->name('proveedores.plantilla');
//Ruta para exportar en Excel el listado de los proveedores
Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.exportar');

Route::post('/importar-proveedores', [ProveedorImportController::class, 'importar'])->name('importar.proveedores');
Route::post('/proveedores/mapear-columnas', [ProveedorImportController::class, 'mapearColumnas'])
    ->name('proveedores.mapear');
Route::post('/proveedores/generar-plantilla-corregida', [ProveedorImportController::class, 'generarArchivoCorregido'])
    ->name('proveedores.generar-corregido');
Route::resource('proveedores', 'App\Http\Controllers\ProveedorController')->middleware('auth');


Route::get('/subir-proveedores', function () {
    return view('importar');
});





//Ruta para las facturas
Route::resource('facturas', 'App\Http\Controllers\FacturaController')->middleware('auth')->except(['show']);
Route::get('/facturas/detalle/{id}', [FacturaController::class, 'showFacturaDetail'])->name('facturas.detail');
Route::put('/facturas/{factura}/update-status', [FacturaController::class, 'updateStatus'])->name('facturas.update-status');


// Ruta resource para gestionar todas las operaciones del HistorialVacacionController
Route::post('/historial-vacacion/{id}/subir', [HistorialVacacionController::class, 'subirArchivo'])
    ->name('historial-vacacion.subir');
Route::get('/historial-vacacion/{id}/descargar', [HistorialVacacionController::class, 'descargarArchivo'])
    ->name('historial-vacacion.descargar');
Route::resource('historial-vacacion', HistorialVacacionController::class);

    
// Pagos
Route::post('/pagos/exportar', [PagoController::class, 'exportarSeleccionados'])->name('pagos.exportar');
Route::patch('/pagos/{id}/importante', [PagoController::class, 'toggleImportante'])
    ->name('pagos.toggleImportante');

Route::get('/pagos/descargar', [PagoController::class, 'descargar'])->name('pagos.descargar');
Route::resource('pagos','App\Http\Controllers\PagoController')->middleware('auth');





// 8. Rutas para exportar PDF
Route::get('empleados/export/pdf', [TrabajadorController::class, 'exportPdf'])->name('empleados.exportPdf');
Route::get('empleados/{id}/exportar-cotizacion', [TrabajadorController::class, 'exportCotizacion'])->name('empleados.exportCotizacion');


// ASISTENCIA
Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
Route::post('/asistencia', [AsistenciaController::class, 'store'])->name('asistencia.store');

// 9. Páginas especiales
Route::get('/under-construction', function () {
    return view('under_construction');
})->name('under.construction');

Route::get('/admin', function () {
    return view('admin.admin');  // Apunta a la vista 'admin/admin.blade.php'
})->name('admin.index')->middleware('auth');





Route::get('/assign-role-admin-marce', function () {
    $luis = User::find(140); // Cambia Y por el ID de Luis en `users`
    $luis->assignRole('admin');
    return "Rol 'admin' asignado a Marcelo.";
});

Route::get('/assign-role-jefe-jp', function () {
    $benjamin = User::find(139); // Cambia X por el ID de Benjamin en `users`
    $benjamin->assignRole('jefe');
    return "Rol 'jefe' asignado a jp.";
});



Route::get('/test-mail', function () {
    Mail::raw('Este es un correo de prueba enviado desde Laravel usando Outlook SMTP.', function ($message) {
        $message->to('eliascorrea@4nlogistica.cl') // <-- Reemplaza con un correo que puedas revisar
                ->subject('Correo de Prueba - Laravel SMTP');
    });

    return 'Correo enviado (si todo salió bien).';
});
// Investigar API
// BACKCOFFE
// 





Route::post('/fake-api/token', function (Request $request) {
    // Simulamos usuario y contraseña correctos
    $usuario = $request->input('Username');
    $contrasena = $request->input('Password');
    $grantType = $request->input('grant_type');

    // Verificamos que los valores coincidan con lo esperado
    if ($usuario === 'admin' && $contrasena === '1234' && $grantType === 'password') {
        return response()->json([
            'access_token' => 'FAKE-TOKEN-123456789',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    } else {
        return response()->json([
            'error' => 'Credenciales inválidas'
        ], 401);
    }
});
