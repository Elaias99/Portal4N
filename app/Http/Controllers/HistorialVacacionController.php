<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistorialVacacion;
use App\Models\Trabajador;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class HistorialVacacionController extends Controller
{


    // Mostrar todas las solicitudes de días (históricas y recientes)
    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Si el usuario no proporciona un trabajador específico, listar todos los trabajadores activos
        $trabajadorId = $request->get('trabajador_id');

        if (!$trabajadorId) {
            $trabajadores = Trabajador::whereHas('sistemaTrabajo', function ($q) {
                    $q->where('nombre', '!=', 'Desvinculado');
                })
                ->whereHas('situacion', function ($q) {
                    $q->where('Nombre', '!=', 'Desvinculado');
                })
                ->whereNull('deleted_at')
                ->orderBy('Nombre', 'asc')
                ->get();

            return view('historial_vacacion.index', compact('trabajadores'));
        }

        // Obtener el trabajador seleccionado (solo si está activo)
        $trabajador = Trabajador::where('id', $trabajadorId)
            ->whereHas('sistemaTrabajo', function ($q) {
                $q->where('nombre', '!=', 'Desvinculado');
            })
            ->whereHas('situacion', function ($q) {
                $q->where('Nombre', '!=', 'Desvinculado');
            })
            ->whereNull('deleted_at')
            ->first();

        if (!$trabajador) {
            return redirect()->route('historial_vacacion.index')
                ->with('error', 'El trabajador no fue encontrado o está desvinculado.');
        }

        // Obtener todas las vacaciones históricas del empleado
        $historialVacaciones = HistorialVacacion::where('trabajador_id', $trabajador->id)
            ->orderBy('fecha_inicio', 'asc')
            ->get();

        // Obtener todas las solicitudes de vacaciones aprobadas
        $solicitudesAprobadas = Solicitud::where('trabajador_id', $trabajador->id)
            ->where('campo', 'Vacaciones')
            ->where('estado', 'aprobado')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calcular los días laborales para cada solicitud reciente (lunes a viernes)
        $solicitudesAprobadas->each(function ($solicitud) {

            if (!$solicitud->vacacion) {
                $solicitud->dias_tomados = 0;
                $solicitud->dias_descontados = 0;
                return;
            }

            $fechaInicio = \Carbon\Carbon::parse($solicitud->vacacion->fecha_inicio);
            $fechaFin = \Carbon\Carbon::parse($solicitud->vacacion->fecha_fin);

            $diasLaborales = 0;

            while ($fechaInicio->lte($fechaFin)) {
                if ($fechaInicio->isWeekday()) {
                    $diasLaborales++;
                }
                $fechaInicio->addDay();
            }

            $solicitud->dias_tomados = $diasLaborales;
            $solicitud->dias_descontados =
                ($solicitud->tipo_dia === 'vacaciones')
                    ? $diasLaborales
                    : 0;
        });

        // Calcular los días descontados para cada registro histórico
        $historialVacaciones->each(function ($historial) {
            $historial->dias_descontados =
                ($historial->tipo_dia === 'vacaciones')
                    ? $historial->dias_laborales
                    : 0;
        });

        // Pasar las solicitudes e historial a la vista
        return view(
            'historial_vacacion.index',
            compact('historialVacaciones', 'solicitudesAprobadas', 'trabajador')
        );
    }








    // Mostrar el formulario para registrar días históricos
    public function create()
    {
        $trabajadores = Trabajador::all();
        return view('historial_vacacion.create', compact('trabajadores'));
    }

    // Almacenar los días históricos en la base de datos
    public function store(Request $request)
    {
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'dias_laborales' => 'required|integer|min:1',
            'tipo_dia' => 'required|string',
            'archivo_respaldo' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        // Obtener el trabajador
        $trabajador = \App\Models\Trabajador::find($request->trabajador_id);

        // Crear el registro de días históricos
        HistorialVacacion::create($request->all());

        // Descontar días proporcionales si es "vacaciones"
        if (strtolower($request->tipo_dia) === 'vacaciones') {
            $nuevoSaldo = max(0, $trabajador->dias_proporcionales - $request->dias_laborales);
            $trabajador->update(['dias_proporcionales' => $nuevoSaldo]);
        }

        return redirect()->route('historial-vacacion.create')->with('success', 'Historial de vacaciones registrado y saldo actualizado.');
    }

    
    public function descargarArchivo($id)
    {
        // Buscar la vacación histórica
        $historial = HistorialVacacion::findOrFail($id);

        // Verificar si el archivo existe
        if ($historial->archivo_respaldo) {
            return response()->download(storage_path('app/' . $historial->archivo_respaldo));
        }

        return redirect()->back()->with('error', 'El archivo no está disponible.');
    }



    public function subirArchivo(Request $request, $id)
    {
        $request->validate([
            'archivo_respaldo' => 'required|file|mimes:pdf|max:2048', // Validación del archivo
        ]);

        // Buscar la vacación histórica
        $historial = HistorialVacacion::findOrFail($id);

        // Guardar el archivo en storage/historial_vacaciones_respaldo
        if ($request->hasFile('archivo_respaldo')) {
            $archivoOriginal = $request->file('archivo_respaldo')->getClientOriginalName();
            $archivoPath = $request->file('archivo_respaldo')->storeAs('historial_vacaciones_respaldo', $archivoOriginal);
            $historial->archivo_respaldo = $archivoPath;
            $historial->save();
        }

        return redirect()->back()->with('success', 'Archivo de respaldo subido correctamente.');
    }




}
