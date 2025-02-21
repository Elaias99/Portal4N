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
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AsistenciaController;
use App\Models\User;
use App\Http\Controllers\SolicitudManualController;






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
route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
//
Route::get('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');



// 6. Rutas para vacaciones
route::get('/vacaciones/create', [VacacionController::class, 'create'])->name('vacaciones.create');// Vista del empleado, para pedir solicitud de vacación
route::post('/vacaciones', [VacacionController::class, 'store'])->name('vacaciones.store');//Valida los campos que se piden cuando se solicitan días

route::get('vacaciones/descargar/{id}', [VacacionController::class, 'descargarArchivo'])->name('vacaciones.descargar');//permitiendo al administrador descargar el archivo adjunto de la solicitud de vacaciones.

Route::post('/solicitudes/vacaciones/{id}/aprobar', [SolicitudController::class, 'approveVacacion'])->middleware('auth')->name('solicitudes.vacaciones.approve');//Botón que aprueba solicitud de VACACIONES
Route::post('/solicitudes/vacaciones/{id}/rechazar', [SolicitudController::class, 'rejectVacacion'])->middleware('auth')->name('solicitudes.vacaciones.reject');//Botón que RECHAZA solicitud de VACACIONES
Route::get('/vacaciones/{id}/descargar-archivo-admin', [VacacionController::class, 'descargarArchivoAdmin'])->name('vacaciones.descargarArchivoAdmin'); //Esta ruta específica se encargará de manejar la descarga del archivo PDF que se crea de manera automática


//ruta para que el empleado pueda acceder a la descarga del archivo que adjunto el administrador cuando aprueba o rechaza una solicitud
Route::get('/vacaciones/{id}/descargar-archivo-respuesta', [VacacionController::class, 'descargarArchivoRespuestaAdmin'])->middleware('auth')->name('vacaciones.descargarArchivoRespuestaAdmin');

//ruta en web.php para que el administrador pueda acceder a la vista centralizada de archivos:
Route::get('/admin/archivos-respaldo', [VacacionController::class, 'mostrarArchivosRespaldo'])
    ->name('admin.archivos-respaldo');



// Ruta para exportar Excel el listado de los empleados
Route::get('/empleados/exportExcel', [TrabajadorController::class, 'exportExcel'])->name('empleados.exportExcel');

//Ruta para exportar en Excel el listado de las facturas
Route::get('/facturas/export', [FacturaController::class, 'export'])->name('facturas.export');
//Ruta para exportar en Excel el listado de los proveedores
Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export');

// 7. Rutas para otros modelos
Route::resource('empresas', '\App\Http\Controllers\EmpresaController')->middleware('auth');
Route::resource('cargos', '\App\Http\Controllers\CargoController')->middleware('auth');
Route::resource('afps', 'App\Http\Controllers\AFPController')->middleware('auth');
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

// Ruta para las compras
Route::resource('compras', CompraController::class);
Route::patch('/compras/{id}/status', [CompraController::class, 'updateStatus'])->name('compras.updateStatus');

// Ruta Dashboard
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');



// Ruta PDF MANUAL
Route::get('/rrhh/generar-pdf', [SolicitudManualController::class, 'formulario'])->name('rrhh.formulario');
Route::post('/rrhh/generar-pdf', [SolicitudManualController::class, 'generarPDF'])->name('rrhh.generar-pdf');

// Tutorial Videos
Route::get('/tutorial', function () {
    return view('tutorial');
})->name('tutorial');

//Ruta para los proveedores
Route::resource('proveedores', 'App\Http\Controllers\ProveedorController')->middleware('auth');

//Ruta para compras
Route::get('compras/{id}/archivo-oc', [CompraController::class, 'descargarArchivoOC'])->name('compras.descargarArchivoOC');
Route::get('compras/{id}/archivo-documento', [CompraController::class, 'descargarArchivoDocumento'])->name('compras.descargarArchivoDocumento');

//Ruta para las facturas
Route::resource('facturas', 'App\Http\Controllers\FacturaController')->middleware('auth')->except(['show']);
Route::get('/facturas/detalle/{id}', [FacturaController::class, 'showFacturaDetail'])->name('facturas.detail');
Route::put('/facturas/{factura}/update-status', [FacturaController::class, 'updateStatus'])->name('facturas.update-status');


// Ruta resource para gestionar todas las operaciones del HistorialVacacionController
Route::resource('historial-vacacion', HistorialVacacionController::class);

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


