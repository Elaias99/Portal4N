<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use App\Models\Vacacion;
use App\Models\HistorialVacacion;
use App\Models\Empresa;
use App\Models\Solicitud;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener empleados con las relaciones necesarias
        $empleados = Trabajador::select('id', 'Nombre', 'ApellidoPaterno', 'ApellidoMaterno', 'CorreoPersonal', 'fecha_inicio_trabajo', 'cargo_id')
            ->with([
                'cargo',
                'vacaciones' => function ($query) {
                    $query->whereHas('solicitud', fn($q) => $q->where('estado', 'aprobado')->where('tipo_dia', 'vacaciones'));
                },
                'historialVacaciones'
            ])->get();

        // Obtener empleados desvinculados con su cargo
        $empleadosDesvinculados = Trabajador::where('situacion_id', 2)
            ->select('id', 'Nombre', 'ApellidoPaterno', 'cargo_id')
            ->with('cargo')
            ->get();

        // Obtener el conteo de empleados por cargo
        $cargos = \App\Models\Cargo::withCount('trabajadors')->get();

        // Obtener solicitudes pendientes con trabajadores
        $solicitudesPendientes = Solicitud::where('estado', 'pendiente')
            ->with('trabajador')
            ->get();

        // Obtener empleados nuevos en el último mes con su cargo
        $empleadosNuevos = Trabajador::where('fecha_inicio_trabajo', '>=', now()->subMonth())
            ->with('cargo')
            ->get();

        // Calcular saldo de días proporcionales para cada empleado
        $empleadosConSaldo = $empleados->map(function ($empleado) {
            $empleado->saldo_dias = $this->calcularDiasProporcionales($empleado);
            return $empleado;
        });

        // Filtrar empleados con saldo negativo (deuda de vacaciones)
        $empleadosConDeuda = $empleadosConSaldo->filter(fn($empleado) => $empleado->saldo_dias < 0);

        // Obtener empleados por empresa
        $empresas = $this->totalEmpleadosPorEmpresa();

        // Retornar la vista con los datos estructurados
        return view('dashboard.index', compact(
            'empleadosDesvinculados', 'empleadosConDeuda', 'empleadosConSaldo',
            'empresas', 'solicitudesPendientes', 'empleadosNuevos', 'cargos'
        ));
    }


    public function totalEmpleadosPorEmpresa()
    {
        return Empresa::withCount('trabajadores')->get();
    }

    private function calcularDiasProporcionales($trabajador)
    {
        if (!$trabajador || !$trabajador->fecha_inicio_trabajo) {
            return 0;
        }

        $fechaInicio = Carbon::parse($trabajador->fecha_inicio_trabajo);
        $mesesCompletosTrabajados = $fechaInicio->diffInMonths(Carbon::now());
        $diasProporcionalesCalculados = (15 / 12) * $mesesCompletosTrabajados;

        // Obtener días de vacaciones aprobados
        $diasTomadosVacaciones = Vacacion::where('trabajador_id', $trabajador->id)
            ->whereHas('solicitud', fn($query) => $query->where('estado', 'aprobado')->where('tipo_dia', 'vacaciones'))
            ->sum('dias');

        // Obtener días históricos de vacaciones
        $diasTomadosHistoricos = HistorialVacacion::where('trabajador_id', $trabajador->id)
            ->where('tipo_dia', 'vacaciones')
            ->sum('dias_laborales');

        return round($diasProporcionalesCalculados - ($diasTomadosVacaciones + $diasTomadosHistoricos), 2);
    }
}
