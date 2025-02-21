<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Auth;
use App\Notifications\SolicitudActualizada;
use App\Notifications\NotificacionAdmin;
use Barryvdh\DomPDF\Facade\Pdf;


class SolicitudController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        $estado = $request->get('estado');
        $jefeId = $user->jefe->id ?? null; // Obtener el ID del jefe si existe

        // Si el usuario tiene un jefe, es un "jefe de área"
        if ($jefeId) {
            // Filtrar solo las solicitudes de los empleados bajo este jefe
            $solicitudes = Solicitud::with('trabajador')
                ->whereHas('trabajador', function ($query) use ($jefeId) {
                    $query->where('id_jefe', $jefeId);
                })
                ->when($estado, function ($query, $estado) {
                    return $query->where('estado', $estado);
                })
                ->orderByRaw("FIELD(estado, 'pendiente', 'aprobado', 'rechazado') ASC")
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Si el usuario no es un jefe, mostrar todas las solicitudes (asumiendo que es admin)
            $solicitudes = Solicitud::with('trabajador')
                ->when($estado, function ($query, $estado) {
                    return $query->where('estado', $estado);
                })
                ->orderByRaw("FIELD(estado, 'pendiente', 'aprobado', 'rechazado') ASC")
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('solicitudes.index', compact('solicitudes'));
    }







    //este esta diseñado para permitir que un empleado cree una nueva solicitud de modificación
    public function create()
    {
        // Retornar la vista del formulario de solicitud
        return view('solicitudes.create');
    }


    public function notificarAdminSolicitud($solicitud)
    {
        // Obtener el jefe directo del trabajador que creó la solicitud
        $jefe = $solicitud->trabajador->jefe->user ?? null; // Obtener el usuario jefe asociado al trabajador

        // Notificar solo al jefe directo si existe
        if ($jefe) {
            $jefe->notify(new NotificacionAdmin($solicitud));
        }
    }




    public function store(Request $request)
    {
        // Validar los datos del formulario, incluyendo el archivo adjunto
        $request->validate([
            'campo' => 'required|string',
            'descripcion' => 'required|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',  // Validar tipo y tamaño del archivo
        ]);

        // Verificar si ya existe una solicitud pendiente o aprobada para este campo
        $solicitudExistente = Solicitud::where('trabajador_id', Auth::user()->trabajador->id)
            ->where('campo', $request->campo)
            ->whereIn('estado', ['pendiente', 'aprobado'])
            ->first();

        if ($solicitudExistente) {
            return redirect()->route('empleados.perfil')->with('warning', 'Ya tienes una solicitud pendiente o aprobada para este campo. Espera la respuesta.');
        }

        // Procesar el archivo si se adjunta
        $archivoPath = null;
        if ($request->hasFile('archivo')) {
            // Guardar el archivo en 'storage/app/solicitudes_adjuntos'
            $archivoPath = $request->file('archivo')->store('solicitudes_adjuntos');
        }

        // Crear la nueva solicitud
        $solicitud = Solicitud::create([
            'trabajador_id' => Auth::user()->trabajador->id,
            'campo' => $request->campo,
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente',
            'archivo' => $archivoPath,  // Almacenar la ruta del archivo si se subió uno
        ]);

        // Notificar a los administradores
        $this->notificarAdminSolicitud($solicitud);

        return redirect()->route('empleados.perfil')->with('success', 'Solicitud enviada con éxito.');
    }


    //Esta función aprueba solo Solicitudes de cambio de campo
    public function approve(Request $request, $id)
    {
        $request->validate([
            'comentario_admin' => 'nullable|string|max:1000',
            'archivo_admin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $solicitud = Solicitud::findOrFail($id);

        // Si la solicitud está pendiente, proceder con la aprobación y enviar notificación
        if ($solicitud->estado === 'pendiente') {
            $solicitud->estado = 'aprobado';
            $solicitud->comentario_admin = $request->comentario_admin;
            $solicitud->fecha_respuesta = now();
            $solicitud->save();

            // Notificar al empleado solo si el estado cambia a aprobado
            $solicitud->trabajador->user->notify(new SolicitudActualizada('aprobada'));
        }

        // Si el administrador ha subido un archivo adicional, guardarlo
        if ($request->hasFile('archivo_admin')) {
            $archivoPath = $request->file('archivo_admin')->store('solicitudes_adjuntos_admin');
            $solicitud->archivo_admin = $archivoPath;
        }

        $solicitud->save();

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud de modificación procesada con éxito.');
    }







    //Esta función rechaza solo Solicitudes de cambio de campo
    public function reject(Request $request, $id)
    {
        $request->validate([
            'comentario_admin' => 'required|string|max:1000',
            'archivo_admin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $solicitud = Solicitud::findOrFail($id);

        // Solo cambiar el estado y enviar notificación si está pendiente
        if ($solicitud->estado === 'pendiente') {
            $solicitud->estado = 'rechazado';
            $solicitud->comentario_admin = $request->comentario_admin;
            $solicitud->fecha_respuesta = now();
            $solicitud->save();

            // Notificar al empleado solo si el estado cambia a rechazado
            $solicitud->trabajador->user->notify(new SolicitudActualizada('rechazada'));
        }

        // Guardar el archivo adicional si se proporciona
        if ($request->hasFile('archivo_admin')) {
            $archivoPath = $request->file('archivo_admin')->store('solicitudes_adjuntos_admin');
            $solicitud->archivo_admin = $archivoPath;
        }

        $solicitud->save();

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud de modificación rechazada con éxito.');
    }


    // Método para que el administrador pueda descargar el archivo que ha adjuntado el empleado al pedir una solicitud
    public function descargarArchivo($id)
    {
        // Buscar la solicitud por su ID
        $solicitud = Solicitud::findOrFail($id);

        // Verificar si la solicitud tiene un archivo
        if ($solicitud->archivo) {
            // Generar un nombre de archivo más descriptivo
            $empleadoNombre = $solicitud->trabajador->Nombre ?? 'Empleado';
            $tipoSolicitud = $solicitud->campo ?? 'Solicitud';
            $fechaSolicitud = $solicitud->created_at->format('Y-m-d') ?? 'Fecha';

            $nombreArchivo = "Solicitud_{$empleadoNombre}_{$tipoSolicitud}_{$fechaSolicitud}.pdf";

            // Servir el archivo desde 'storage/app/solicitudes_adjuntos'
            return response()->download(storage_path('app/' . $solicitud->archivo), $nombreArchivo);
        } else {
            return redirect()->back()->with('error', 'No hay archivo adjunto para esta solicitud.');
        }
    }


    //Función para que el empleado pueda descargar el archivo que adjunto el administrador
    public function descargaArchivoAEmpleado($id)
    {
        // Buscar la solicitud por su ID
        $solicitud = Solicitud::findOrFail($id);

        // Verificar si la solicitud tiene un archivo adjunto del administrador
        if ($solicitud->archivo_admin) {
            // Generar un nombre descriptivo para el archivo
            $empleadoNombre = $solicitud->trabajador->Nombre ?? 'Empleado'; // Asegúrate de tener la relación trabajador en el modelo
            $campoModificacion = ucfirst($solicitud->campo) ?? 'Modificacion';
            $fechaSolicitud = $solicitud->created_at->format('Y-m-d') ?? 'Fecha';

            // Nombre del archivo: "Elias_Respaldo_AFP_2025-01-06.pdf"
            $nombreArchivo = "{$empleadoNombre}_Respaldo_{$campoModificacion}_{$fechaSolicitud}.pdf";

            // Servir el archivo desde 'storage/app/solicitudes_adjuntos_admin'
            return response()->download(storage_path('app/' . $solicitud->archivo_admin), $nombreArchivo);
        } else {
            return redirect()->back()->with('error', 'No hay archivo adjunto para esta solicitud.');
        }
    }










    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////// SOLICITUDES SOLO DE VACACIONES /////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //Método para mostrar todas las solicitudes de vacaciones que ha echo el empleado
    // Método para mostrar todas las solicitudes de vacaciones que ha hecho el empleado
    public function vacaciones(Request $request)
    {
        $user = Auth::user();
        $estado = $request->get('estado');
        $jefeId = $user->jefe->id ?? null; // Obtener el ID del jefe si existe

        // Filtrar solicitudes de vacaciones
        $solicitudes = Solicitud::where('campo', 'Vacaciones')
            ->when($jefeId, function ($query) use ($jefeId) {
                $query->whereHas('trabajador', function ($query) use ($jefeId) {
                    $query->where('id_jefe', $jefeId);
                });
            })
            ->when($estado, function ($query, $estado) {
                return $query->where('estado', $estado);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Asociar días solicitados directamente desde la columna 'dias', validando la relación
        $solicitudes->each(function ($solicitud) {
            $solicitud->dias_laborales = $solicitud->vacacion ? $solicitud->vacacion->dias : 0; // Validar relación antes de acceder
        });

        return view('solicitudes.vacaciones', compact('solicitudes'));
    }

    // Función para eliminar acentos
    public function eliminarAcentos($cadena) {
        $acentos = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'];
        $sinAcentos = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'];
        return str_replace($acentos, $sinAcentos, $cadena);
    }



    public function approveVacacion(Request $request, $id)
    {
        $solicitud = Solicitud::where('id', $id)->where('campo', 'Vacaciones')->firstOrFail();

        // Validación condicional
        $rules = [
            'archivo_respuesta_admin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ];
        if ($solicitud->estado === 'pendiente') {
            $rules['comentario_admin'] = 'required|string|max:1000';
        }
        $request->validate($rules);

        $vacacion = $solicitud->vacacion;
        $notificar = false; // Bandera para controlar la notificación
        $diasDescontados = 0; // Inicializar la variable para evitar el error

        // Aprobar la solicitud solo si está pendiente
        if ($solicitud->estado === 'pendiente') {
            $solicitud->estado = 'aprobado';
            $solicitud->comentario_admin = $request->comentario_admin;
            $solicitud->fecha_respuesta = now();
            $solicitud->save();

            // Obtener los días descontados basados en el tipo de día
            if ($solicitud->tipo_dia === 'vacaciones') {
                $trabajador = $solicitud->trabajador;

                // Descuento de días proporcionales
                $diasDescontados = $vacacion->dias; // Guardar los días solicitados para el PDF
                $diasProporcionalesRestantes = max(0, $trabajador->dias_proporcionales - $diasDescontados);
                $trabajador->update(['dias_proporcionales' => $diasProporcionalesRestantes]);
            }

            // Generar PDF de confirmación
            $jefeAutenticado = Auth::user();
            $firmaPath = $jefeAutenticado ? storage_path("app/Firmas/Firma" . str_replace(' ', '', $this->eliminarAcentos($jefeAutenticado->name)) . ".png") : null;

            $pdf = PDF::loadView('pdf.solicitud_vacaciones', compact('solicitud', 'firmaPath', 'diasDescontados'));
            $fileName = 'vacacion_solicitud_' . $vacacion->id . '.pdf';
            $pdf->save(storage_path('app/vacaciones_adjuntos_admin/pdfs/' . $fileName));

            $vacacion->archivo_admin = 'vacaciones_adjuntos_admin/pdfs/' . $fileName;
            $notificar = true; // Solo notificar si se aprueba
        }

        // Guardar archivo adicional si se proporciona
        if ($request->hasFile('archivo_respuesta_admin')) {
            $archivoPath = $request->file('archivo_respuesta_admin')->store('vacaciones_adjuntos_admin/respuestas');
            $vacacion->archivo_respuesta_admin = $archivoPath;
        }

        $vacacion->save();

        // Notificar solo si el estado cambió a "aprobado"
        if ($notificar) {
            $solicitud->trabajador->user->notify(new SolicitudActualizada('aprobada'));
        }

        return redirect()->route('solicitudes.vacaciones')->with('success', 'Solicitud procesada con éxito.');
    }


    

    public function rejectVacacion(Request $request, $id)
    {
        $solicitud = Solicitud::where('id', $id)->where('campo', 'Vacaciones')->firstOrFail();

        $request->validate([
            'comentario_admin' => 'required|string|max:1000',
            'archivo_respuesta_admin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $vacacion = $solicitud->vacacion;
        $notificar = false;

        // Rechazar solo si está pendiente
        if ($solicitud->estado === 'pendiente') {
            $solicitud->estado = 'rechazado';
            $solicitud->comentario_admin = $request->comentario_admin;
            $solicitud->fecha_respuesta = now();
            $solicitud->save();
            $notificar = true; // Notificar solo si se rechaza
        }

        // Guardar archivo adicional si se proporciona
        if ($request->hasFile('archivo_respuesta_admin')) {
            $archivoPath = $request->file('archivo_respuesta_admin')->store('vacaciones_adjuntos_admin/respuestas');
            $vacacion->archivo_respuesta_admin = $archivoPath;
        }

        $vacacion->save();

        // Notificar solo si el estado cambió a "rechazado"
        if ($notificar) {
            $solicitud->trabajador->user->notify(new SolicitudActualizada('rechazada'));
        }

        return redirect()->route('solicitudes.vacaciones')->with('success', 'Solicitud rechazada con éxito.');
    }

}
