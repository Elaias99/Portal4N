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
        $trabajadorId = $request->get('trabajador_id');

        if (!$trabajadorId) {
            return redirect()->route('empleados.index')
                ->with('error', 'No se especificó un trabajador.');
        }

        $trabajador = Trabajador::with(['cargo', 'empresa', 'jefe', 'situacion', 'sistemaTrabajo'])
            ->where('id', $trabajadorId)
            ->whereHas('sistemaTrabajo', function ($q) {
                $q->where('nombre', '!=', 'Desvinculado');
            })
            ->whereHas('situacion', function ($q) {
                $q->where('Nombre', '!=', 'Desvinculado');
            })
            ->whereNull('deleted_at')
            ->first();

        if (!$trabajador) {
            return redirect()->route('empleados.index')
                ->with('error', 'El trabajador no fue encontrado o está desvinculado.');
        }

        $historialVacaciones = HistorialVacacion::where('trabajador_id', $trabajador->id)
            ->orderBy('fecha_inicio', 'asc')
            ->get();

        $solicitudesAprobadas = Solicitud::with('vacacion')
            ->where('trabajador_id', $trabajador->id)
            ->where('campo', 'Vacaciones')
            ->where('estado', 'aprobado')
            ->orderBy('created_at', 'desc')
            ->get();

        // Historial manual: marcar días descontados
        $historialVacaciones->each(function ($historial) {
            $historial->dias_descontados = ($historial->tipo_dia === 'vacaciones')
                ? $historial->dias_laborales
                : 0;
        });

        // Solicitudes aprobadas: marcar días tomados y descontados
        $solicitudesAprobadas->each(function ($solicitud) {
            if (!$solicitud->vacacion) {
                $solicitud->dias_tomados = 0;
                $solicitud->dias_descontados = 0;
                return;
            }

            // Aquí uso el valor ya guardado en vacacion->dias
            // para mantener consistencia con el flujo actual del sistema
            $dias = $solicitud->vacacion->dias ?? 0;

            $solicitud->dias_tomados = $dias;
            $solicitud->dias_descontados = ($solicitud->tipo_dia === 'vacaciones') ? $dias : 0;
        });

        // ==========================
        // Resumen general de vacaciones
        // ==========================

        $diasHistoricosDescontados = $historialVacaciones
            ->where('tipo_dia', 'vacaciones')
            ->sum('dias_laborales');

        $diasSolicitudesDescontados = $solicitudesAprobadas
            ->where('tipo_dia', 'vacaciones')
            ->sum(function ($solicitud) {
                return $solicitud->vacacion->dias ?? 0;
            });

        $diasTotalDescontados = $diasHistoricosDescontados + $diasSolicitudesDescontados;

        // Cálculo teórico acumulado desde fecha de contratación
        $diasAcumuladosTeoricos = 0;

        if ($trabajador->fecha_inicio_trabajo) {
            $fechaInicio = \Carbon\Carbon::parse($trabajador->fecha_inicio_trabajo);
            $fechaActual = \Carbon\Carbon::now();

            $diasMesInicio = $fechaInicio->daysInMonth;
            $diasTrabajadosMesInicio = $diasMesInicio - $fechaInicio->day + 1;
            $proporcionMesInicio = $diasTrabajadosMesInicio / $diasMesInicio;

            $mesesCompletosTrabajados = $fechaInicio->diffInMonths($fechaActual) - 1;

            $diasAcumuladosTeoricos = round(
                (15 / 12) * ($mesesCompletosTrabajados + $proporcionMesInicio),
                2
            );
        }

        // Saldo real: sí puede ser negativo
        $saldoReal = round($diasAcumuladosTeoricos - $diasTotalDescontados, 2);

        // Saldo mostrado: igual al comportamiento actual del sistema
        $saldoMostrado = max($saldoReal, 0);

        $detalleAlerta = null;

        if ($saldoReal < 0) {
            $detalleAlerta = 'El trabajador tiene más días descontados que los acumulados teóricamente. Revisa si existen cargas manuales o registros históricos que generaron déficit.';
        } elseif ($saldoMostrado == 0 && $diasTotalDescontados > 0) {
            $detalleAlerta = 'El trabajador no tiene saldo disponible actualmente.';
        }

        $resumen = [
            'dias_acumulados_teoricos' => $diasAcumuladosTeoricos,
            'dias_historicos_descontados' => $diasHistoricosDescontados,
            'dias_solicitudes_descontados' => $diasSolicitudesDescontados,
            'dias_total_descontados' => $diasTotalDescontados,
            'saldo_mostrado' => $saldoMostrado,
            'saldo_real' => $saldoReal,
            'detalle_alerta' => $detalleAlerta,
        ];

        return view('historial_vacacion.index', compact(
            'historialVacaciones',
            'solicitudesAprobadas',
            'trabajador',
            'resumen'
        ));
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
