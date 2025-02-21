<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vacacion;
use App\Models\Solicitud;
use App\Models\HistorialVacacion;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NotificacionAdminVacaciones;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class VacacionController extends Controller
{

    public function create()
    {
        $user = Auth::user();
        $solicitudPendiente = Vacacion::solicitudesPendientes($user->trabajador->id);
        $diasProporcionales = $this->diaProporcional();

        $response = Http::get('https://apis.digital.gob.cl/fl/feriados');
        $feriados = $response->successful() ? $response->json() : [];

        return view('vacaciones.create', compact('diasProporcionales', 'solicitudPendiente', 'feriados'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'tipo_dia' => 'required|in:vacaciones,administrativo,sin_goce_de_sueldo,Permiso_fuerza_mayor,licencia_medica',
            'dias' => 'required|integer|min:1',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $tipoDia = $request->input('tipo_dia');
        $trabajador = Auth::user()->trabajador;

        if ($tipoDia === 'vacaciones') {
            $diasProporcionales = $this->diaProporcional();
            if ($request->dias > $diasProporcionales) {
                return redirect()->back()->withErrors(['msg' => 'No puedes solicitar más días de los que tienes acumulados.']);
            }
            $trabajador->update([
                'dias_proporcionales' => max(0, $trabajador->dias_proporcionales - $request->dias)
            ]);
        }

        $solicitud = Solicitud::create([
            'trabajador_id' => $trabajador->id,
            'campo' => 'Vacaciones',
            'descripcion' => 'Solicitud de ' . $tipoDia . ' del ' . $request->fecha_inicio . ' al ' . $request->fecha_fin,
            'estado' => 'pendiente',
            'tipo_dia' => $tipoDia,
        ]);


        $archivoPath = null;
        if ($request->hasFile('archivo')) {
            $archivoOriginal = $request->file('archivo')->getClientOriginalName();
            $archivoPath = $request->file('archivo')->storeAs('solicitudes_adjuntos/vacaciones', $archivoOriginal);
        }

        Vacacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'dias' => $request->dias,
            'estado' => 'pendiente',
            'solicitud_id' => $solicitud->id,
            'archivo' => $archivoPath,
        ]);

        $jefe = $trabajador->jefe->user ?? null;
        if ($jefe) {
            $jefe->notify(new NotificacionAdminVacaciones($solicitud));
        }

        return redirect()->route('vacaciones.create')->with('success', 'Solicitud de días enviada correctamente.');
    }



    public function diaProporcional()
    {
        $user = Auth::user();
        $trabajador = $user->trabajador;

        if (!$trabajador || !$trabajador->fecha_inicio_trabajo) {
            return 0;
        }

        $fechaInicio = Carbon::parse($trabajador->fecha_inicio_trabajo);
        $fechaActual = Carbon::now();

        $diasMesInicio = $fechaInicio->daysInMonth;
        $diasTrabajadosMesInicio = $diasMesInicio - $fechaInicio->day + 1;
        $proporcionMesInicio = $diasTrabajadosMesInicio / $diasMesInicio;

        $mesesCompletosTrabajados = $fechaInicio->diffInMonths($fechaActual) - 1;
        $diasProporcionalesCalculados = (15 / 12) * ($mesesCompletosTrabajados + $proporcionMesInicio);

        // Incluir solo solicitudes de tipo "vacaciones" aprobadas
        $diasTomadosVacaciones = Vacacion::whereHas('solicitud', function ($query) {
                $query->where('estado', 'aprobado')
                    ->where('tipo_dia', 'vacaciones'); // Filtrar solo solicitudes de tipo "vacaciones"
            })
            ->where('trabajador_id', $trabajador->id)
            ->sum('dias');

        $diasTomadosHistoricos = HistorialVacacion::where('trabajador_id', $trabajador->id)
            ->where('tipo_dia', 'vacaciones')
            ->sum('dias_laborales');

        $diasProporcionalesRestantes = max(round($diasProporcionalesCalculados - ($diasTomadosVacaciones + $diasTomadosHistoricos), 2), 0);



        return $diasProporcionalesRestantes;
    }



    private function obtenerDiasHabiles($fechaInicio, $fechaFin)
    {
        $fechaInicio = Carbon::parse($fechaInicio);
        $fechaFin = Carbon::parse($fechaFin);

        // Obtener los feriados desde la API
        $response = Http::get('https://apis.digital.gob.cl/fl/feriados');
        $feriados = $response->successful()
            ? collect($response->json())->pluck('fecha') // Extraer solo las fechas
            : collect();

        $diasHabiles = 0;
        while ($fechaInicio->lessThanOrEqualTo($fechaFin)) {
            // Excluir fines de semana y feriados
            if ($fechaInicio->isWeekday() && !$feriados->contains($fechaInicio->toDateString())) {
                $diasHabiles++;
            }
            $fechaInicio->addDay();
        }
        return $diasHabiles;
    }


    private function validarDiasProporcionales($fechaInicio, $fechaFin)
    {
        $diasProporcionales = $this->diaProporcional();
        $diasSolicitados = $this->obtenerDiasHabiles($fechaInicio, $fechaFin);

        if ($diasSolicitados > $diasProporcionales) {
            return ['error' => 'No puedes solicitar más días de los que tienes acumulados.'];
        }

        return ['success' => true];
    }















    //Método para que el administrador pueda descargar el archivo que a adjuntado el emplado al pedir una solicitud de vacación
    // Método para que el administrador pueda descargar el archivo adjuntado por el empleado al pedir una solicitud de vacación
    public function descargarArchivo($id)
    {
        // Buscar la solicitud de vacaciones por su ID
        $vacacion = Vacacion::findOrFail($id);

        // Verificar si la solicitud tiene un archivo adjunto
        if ($vacacion->archivo) {
            // Generar un nombre descriptivo para el archivo
            $empleadoNombre = $vacacion->trabajador->Nombre ?? 'Empleado'; // Relación trabajador en modelo Vacacion
            $fechaInicio = $vacacion->fecha_inicio->format('Y-m-d') ?? 'fecha_inicio';
            $fechaFin = $vacacion->fecha_fin->format('Y-m-d') ?? 'fecha_fin';

            // Nombre del archivo: "Elias_SolicitudVacaciones_2025-01-06_a_2025-01-15.pdf"
            $nombreArchivo = "{$empleadoNombre}_SolicitudDías_{$fechaInicio}_a_{$fechaFin}.pdf";

            // Descargar el archivo desde 'storage/app/solicitudes_adjuntos/vacaciones'
            return response()->download(storage_path('app/' . $vacacion->archivo), $nombreArchivo);
        } else {
            return redirect()->back()->with('error', 'No hay archivo adjunto para esta solicitud.');
        }
    }


    // Permite al empleado descargar el archivo PDF generado automáticamente cuando su solicitud es aprobada.
    public function descargarArchivoAdmin($id)
    {
        // Buscar la vacación por su ID
        $vacacion = Vacacion::findOrFail($id);

        // Verificar si el archivo del administrador (PDF generado automáticamente) existe
        if ($vacacion->archivo_admin) {
            // Descargar el archivo PDF desde 'vacaciones_adjuntos_admin/pdfs'
            return response()->download(storage_path('app/' . $vacacion->archivo_admin));
        }

        return redirect()->back()->with('warning', 'El archivo no está disponible.');
    }


    //Método para que el admin pueda descargar el archivo adjunto que dejo como respaldo
    public function descargarArchivoRespuestaAdmin($id)
    {
        // Buscar la vacación por su ID
        $vacacion = Vacacion::findOrFail($id);

        // Verificar si el archivo adjunto del administrador existe
        if ($vacacion->archivo_respuesta_admin) {
            // Obtener detalles desde el modelo asociado 'Solicitud'
            $solicitud = $vacacion->solicitud; // Relación definida entre 'Vacacion' y 'Solicitud'
            $empleadoNombre = $vacacion->trabajador->Nombre ?? 'Empleado'; // Relación trabajador en el modelo Vacacion
            $tipoDia = ucfirst(str_replace('_', ' ', $solicitud->tipo_dia)) ?? 'TipoDia'; // Convertir el tipo de día a formato legible
            $fechaInicio = $vacacion->fecha_inicio->format('Y-m-d') ?? 'FechaInicio';
            $fechaFin = $vacacion->fecha_fin->format('Y-m-d') ?? 'FechaFin';

            // Nombre del archivo: "Elias_Respaldo_Vacaciones_2025-01-06_a_2025-01-15.pdf"
            $nombreArchivo = "{$empleadoNombre}_Respaldo_{$tipoDia}_{$fechaInicio}_a_{$fechaFin}.pdf";

            // Descargar el archivo desde 'vacaciones_adjuntos_admin/respuestas'
            return response()->download(storage_path('app/' . $vacacion->archivo_respuesta_admin), $nombreArchivo);
        }

        return redirect()->back()->with('warning', 'El archivo no está disponible.');
    }



    //Este método permite al administrador ver una lista completa de todos los archivos de respaldo
    public function mostrarArchivosRespaldo()
    {
        // Obtener respaldos de vacaciones con archivos adjuntos del administrador
        $vacacionesConRespaldo = Vacacion::whereNotNull('archivo_respuesta_admin')
            ->with('trabajador')
            ->get()
            ->groupBy('trabajador_id'); // Agrupamos por trabajador_id

        // Obtener respaldos de solicitudes de cambios con archivos adjuntos del administrador
        $solicitudesConRespaldo = Solicitud::whereNotNull('archivo_admin')
            ->with('trabajador')
            ->get()
            ->groupBy('trabajador_id'); // Agrupamos por trabajador_id

        // Crear una colección de empleados con sus archivos de respaldo
        $empleadosConRespaldo = [];
        foreach ($vacacionesConRespaldo as $trabajador_id => $vacaciones) {
            $empleadosConRespaldo[$trabajador_id]['vacaciones'] = $vacaciones;
        }
        foreach ($solicitudesConRespaldo as $trabajador_id => $solicitudes) {
            $empleadosConRespaldo[$trabajador_id]['modificaciones'] = $solicitudes;
        }

        // Pasar la colección de empleados con sus archivos de respaldo a la vista
        return view('admin.archivos_respaldo', compact('empleadosConRespaldo'));
    }







}
