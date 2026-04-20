<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vacacion;
use App\Models\Solicitud;
use App\Models\HistorialVacacion;
use Illuminate\Support\Facades\Auth;
use App\Models\Trabajador;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Mail\VacacionesSolicitadas;
use Illuminate\Support\Facades\Mail;
use App\Notifications\NotificacionAdminVacaciones;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VacacionDisponibleExport;
use App\Services\CalendarioChileService;


class VacacionController extends Controller
{

    protected $calendarioChileService;

    public function __construct(CalendarioChileService $calendarioChileService)
    {
        $this->calendarioChileService = $calendarioChileService;
    }



    public function create()
    {
        $trabajador = $this->obtenerTrabajadorAutenticado();

        if (!$trabajador) {
            return redirect()->route('home')->withErrors([
                'msg' => 'No se pudo encontrar el perfil del trabajador asociado.'
            ]);
        }

        $solicitudPendiente = Vacacion::solicitudesPendientes($trabajador->id);
        $diasProporcionales = $this->diaProporcional($trabajador);

        return view('vacaciones.create', compact(
            'diasProporcionales',
            'solicitudPendiente'
        ));
    }


    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'tipo_dia' => 'required|in:vacaciones,administrativo,sin_goce_de_sueldo,Permiso_fuerza_mayor,licencia_medica',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $tipoDia = $request->input('tipo_dia');
        $trabajador = $this->obtenerTrabajadorAutenticado();

        if (!$trabajador) {
            return redirect()->back()->withErrors([
                'msg' => 'No se pudo encontrar el perfil del trabajador asociado.'
            ])->withInput();
        }

        $resultado = DB::transaction(function () use ($request, $trabajador, $tipoDia) {
            $trabajadorBloqueado = Trabajador::where('id', $trabajador->id)
                ->lockForUpdate()
                ->first();

            if (!$trabajadorBloqueado) {
                throw ValidationException::withMessages([
                    'msg' => 'No se pudo encontrar el perfil del trabajador asociado.'
                ]);
            }

            $tienePendiente = Solicitud::where('trabajador_id', $trabajadorBloqueado->id)
                ->where('campo', 'Vacaciones')
                ->where('estado', 'pendiente')
                ->exists();

            if ($tienePendiente) {
                throw ValidationException::withMessages([
                    'msg' => 'Ya tienes una solicitud pendiente. Debes esperar la respuesta antes de crear otra.'
                ]);
            }

            // Calcular días reales usando el service
            $calculo = $this->calendarioChileService->calcularDiasHabiles(
                $request->fecha_inicio,
                $request->fecha_fin
            );

            $diasCalculados = $calculo['dias_habiles'];

            if ($diasCalculados <= 0) {
                throw ValidationException::withMessages([
                    'msg' => 'El rango seleccionado no contiene días hábiles válidos para solicitar.'
                ]);
            }

            $duplicadaExacta = Vacacion::where('trabajador_id', $trabajadorBloqueado->id)
                ->whereDate('fecha_inicio', $request->fecha_inicio)
                ->whereDate('fecha_fin', $request->fecha_fin)
                ->where('dias', $diasCalculados)
                ->whereHas('solicitud', function ($q) use ($tipoDia) {
                    $q->where('campo', 'Vacaciones')
                    ->where('tipo_dia', $tipoDia)
                    ->whereIn('estado', ['pendiente', 'aprobado']);
                })
                ->exists();

            if ($duplicadaExacta) {
                throw ValidationException::withMessages([
                    'msg' => 'Ya existe una solicitud igual para esas fechas.'
                ]);
            }

            $tieneTraslape = Vacacion::where('trabajador_id', $trabajadorBloqueado->id)
                ->whereDate('fecha_inicio', '<=', $request->fecha_fin)
                ->whereDate('fecha_fin', '>=', $request->fecha_inicio)
                ->whereHas('solicitud', function ($q) {
                    $q->where('campo', 'Vacaciones')
                    ->whereIn('estado', ['pendiente', 'aprobado']);
                })
                ->exists();

            if ($tieneTraslape) {
                throw ValidationException::withMessages([
                    'msg' => 'Las fechas solicitadas se cruzan con otra solicitud ya registrada.'
                ]);
            }

            if ($tipoDia === 'vacaciones') {
                $diasProporcionales = $this->diaProporcional($trabajadorBloqueado);

                if ($diasCalculados > $diasProporcionales) {
                    throw ValidationException::withMessages([
                        'msg' => 'No puedes solicitar más días de los que tienes acumulados.'
                    ]);
                }
            }

            $archivoPath = null;
            if ($request->hasFile('archivo')) {
                $archivoPath = $request->file('archivo')->store('solicitudes_adjuntos/vacaciones');
            }

            $solicitud = Solicitud::create([
                'trabajador_id' => $trabajadorBloqueado->id,
                'campo' => 'Vacaciones',
                'descripcion' => 'Solicitud de ' . $tipoDia . ' del ' . $request->fecha_inicio . ' al ' . $request->fecha_fin,
                'estado' => 'pendiente',
                'tipo_dia' => $tipoDia,
            ]);

            $vacacion = Vacacion::create([
                'trabajador_id' => $trabajadorBloqueado->id,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'dias' => $diasCalculados,
                'solicitud_id' => $solicitud->id,
                'archivo' => $archivoPath,
            ]);

            return [
                'solicitud' => $solicitud,
                'vacacion' => $vacacion,
                'trabajador' => $trabajadorBloqueado,
                'calculo' => $calculo,
            ];
        });

        $solicitud = $resultado['solicitud'];
        $trabajador = $resultado['trabajador'];

        $jefe = $trabajador->jefe->user ?? null;

        // if ($jefe) {
        //     $jefe->notify(new NotificacionAdminVacaciones($solicitud));
        //     Mail::to($jefe->email)->send(new VacacionesSolicitadas($solicitud));
        // }

        return redirect()->route('vacaciones.create')
            ->with('success', 'Solicitud de días enviada correctamente.');
    }


    public function diaProporcional(Trabajador $trabajador)
    {
        if (!$trabajador->fecha_inicio_trabajo) {
            return 0;
        }

        $fechaInicio = Carbon::parse($trabajador->fecha_inicio_trabajo);
        $fechaActual = Carbon::now();

        $diasMesInicio = $fechaInicio->daysInMonth;
        $diasTrabajadosMesInicio = $diasMesInicio - $fechaInicio->day + 1;
        $proporcionMesInicio = $diasTrabajadosMesInicio / $diasMesInicio;

        $mesesCompletosTrabajados = $fechaInicio->diffInMonths($fechaActual) - 1;
        $diasProporcionalesCalculados =
            (15 / 12) * ($mesesCompletosTrabajados + $proporcionMesInicio);

        $diasTomadosVacaciones = Vacacion::whereHas('solicitud', function ($q) {
                $q->where('estado', 'aprobado')
                ->where('tipo_dia', 'vacaciones');
            })
            ->where('trabajador_id', $trabajador->id)
            ->sum('dias');

        $diasTomadosHistoricos = HistorialVacacion::where('trabajador_id', $trabajador->id)
            ->where('tipo_dia', 'vacaciones')
            ->sum('dias_laborales');

        return max(
            round($diasProporcionalesCalculados - ($diasTomadosVacaciones + $diasTomadosHistoricos), 2),
            0
        );
    }




    public function misVacaciones()
    {
        $user = Auth::user();
        $resolvedEmail = resolvePerfilEmail($user->email);

        $trabajador = Trabajador::whereHas('user', function ($q) use ($resolvedEmail) {
                $q->where('email', $resolvedEmail);
            })
            ->whereNull('deleted_at')
            ->first();

        if (!$trabajador) {
            return redirect()->back()->withErrors([
                'msg' => 'No se pudo encontrar el perfil del trabajador asociado.'
            ]);
        }

        // Vacaciones históricas
        $historialVacaciones = HistorialVacacion::where('trabajador_id', $trabajador->id)
            ->orderBy('fecha_inicio', 'asc')
            ->get()
            ->each(function ($historial) {
                $historial->dias_descontados = ($historial->tipo_dia === 'vacaciones') ? $historial->dias_laborales : 0;
            });

        // Solicitudes aprobadas
        $solicitudesAprobadas = Solicitud::where('trabajador_id', $trabajador->id)
            ->where('campo', 'Vacaciones')
            ->where('estado', 'aprobado')
            ->orderBy('created_at', 'desc')
            ->with('vacacion')
            ->get()
            ->each(function ($solicitud) {
                $vac = $solicitud->vacacion;
                $dias = $vac ? $vac->dias : 0;
                $solicitud->dias_tomados = $dias;
                $solicitud->dias_descontados = ($solicitud->tipo_dia === 'vacaciones') ? $dias : 0;
            });

        return view('vacaciones.mis_vacaciones', compact('historialVacaciones', 'solicitudesAprobadas', 'trabajador'));
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
        $vacacionesConRespaldo = Vacacion::whereNotNull('archivo_respuesta_admin')
            ->whereHas('trabajador', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->with(['trabajador' => function ($q) {
                $q->whereNull('deleted_at');
            }])
            ->get()
            ->groupBy('trabajador_id');

        $solicitudesConRespaldo = Solicitud::whereNotNull('archivo_admin')
            ->whereHas('trabajador', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->with(['trabajador' => function ($q) {
                $q->whereNull('deleted_at');
            }])
            ->get()
            ->groupBy('trabajador_id');

        $empleadosConRespaldo = [];
        foreach ($vacacionesConRespaldo as $trabajador_id => $vacaciones) {
            $empleadosConRespaldo[$trabajador_id]['vacaciones'] = $vacaciones;
        }
        foreach ($solicitudesConRespaldo as $trabajador_id => $solicitudes) {
            $empleadosConRespaldo[$trabajador_id]['modificaciones'] = $solicitudes;
        }

        return view('admin.archivos_respaldo', compact('empleadosConRespaldo'));
    }



    public function exportarDisponibilidad()
    {
        $nombreArchivo = 'vacaciones_empleados_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new VacacionDisponibleExport, $nombreArchivo);
    }


    private function obtenerTrabajadorAutenticado()
    {
        $user = Auth::user();
        $resolvedEmail = resolvePerfilEmail($user->email);

        return Trabajador::whereHas('user', function ($q) use ($resolvedEmail) {
                $q->where('email', $resolvedEmail);
            })
            ->whereNull('deleted_at')
            ->first();
    }



    







}
