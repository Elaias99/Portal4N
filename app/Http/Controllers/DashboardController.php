<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // Obtener empleados desvinculados con su cargo
        $empleadosDesvinculados = Trabajador::where('situacion_id', 2)
            ->select('id', 'Nombre', 'ApellidoPaterno', 'cargo_id')
            ->with('cargo')
            ->get();

        // Obtener solicitudes pendientes con trabajadores
        $solicitudesPendientes = Solicitud::where('estado', 'pendiente')
            ->with('trabajador')
            ->get();

        // Obtener empleados nuevos en el último mes con su cargo
        $empleadosNuevos = Trabajador::where('fecha_inicio_trabajo', '>=', now()->subMonth())
            ->with('cargo')
            ->get();

        // Obtener todas las vacaciones aprobadas
        $vacacionesAprobadas = Vacacion::whereHas('solicitud', function ($query) {
            $query->where('estado', 'aprobado');
        })->with('trabajador')->get();

        // Obtener empleados por empresa
        $empresas = $this->totalEmpleadosPorEmpresa();

        // Obtener datos generales
        $listas = $this->obtenerListas();

        // Obtener resumen (empleados con saldo, cargos optimizados)
        $resumen = $this->obtenerResumen();

        // Obtener validaciones para la vista
        $validaciones = $this->validacionesVista(
            $listas['solicitudesPendientes'], 
            $listas['empleadosDesvinculados'], 
            $listas['empleadosNuevos']
        );

        // Obtener el usuario autenticado
        $user = Auth::user();

        return view('dashboard.index', array_merge([
            'empleadosDesvinculados' => $empleadosDesvinculados,
            'solicitudesPendientes' => $solicitudesPendientes,
            'empleadosNuevos' => $empleadosNuevos,
            'empresas' => $empresas,
            'user' => $user, // Se envía directamente a la vista
            'vacacionesAprobadas' => $vacacionesAprobadas, // ✅ Se pasa correctamente
        ], $listas, $resumen, $validaciones));
        
    }



    public function totalEmpleadosPorEmpresa()
    {
        return Empresa::withCount('trabajadores')->get();
    }







    // FUNCIONES PRIVADAS


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



    private function obtenerResumen()
    {
        // Obtener empleados con relaciones necesarias
        $empleados = Trabajador::select('id', 'Nombre', 'ApellidoPaterno', 'ApellidoMaterno', 'CorreoPersonal', 'fecha_inicio_trabajo', 'cargo_id')
            ->with([
                'cargo',
                'vacaciones' => function ($query) {
                    $query->whereHas('solicitud', fn($q) => $q->where('estado', 'aprobado')->where('tipo_dia', 'vacaciones'));
                },
                'historialVacaciones'
            ])->get();

        // Calcular saldo de días proporcionales para cada empleado
        $empleadosConSaldo = $empleados->map(function ($empleado) {
            $empleado->saldo_dias = $this->calcularDiasProporcionales($empleado);
            return $empleado;
        });

        // Filtrar empleados con saldo negativo (deuda de vacaciones)
        $empleadosConDeuda = $empleadosConSaldo->filter(fn($empleado) => $empleado->saldo_dias < 0);

        // Contar empleados con saldo (en lugar de usar `count()` en la vista)
        $empleadosConSaldoCount = $empleadosConSaldo->count();

        // Obtener cargos con cantidad de trabajadores y dividir en 3 grupos
        $cargos = \App\Models\Cargo::withCount('trabajadors')->get();
        $cargosChunked = $cargos->chunk(ceil($cargos->count() / 3));

        return compact('empleadosConSaldo', 'empleadosConSaldoCount', 'empleadosConDeuda', 'cargosChunked');
    }


    private function obtenerListas()
    {
        return [
            // Obtener empleados desvinculados con su cargo
            'empleadosDesvinculados' => Trabajador::where('situacion_id', 2)
                ->select('id', 'Nombre', 'ApellidoPaterno', 'cargo_id')
                ->with('cargo')
                ->get(),

            // Obtener solicitudes pendientes con el trabajador
            'solicitudesPendientes' => Solicitud::where('estado', 'pendiente')
                ->with('trabajador:id,Nombre')
                ->get()
                ->map(function ($solicitud) {
                    // Aplicamos ucfirst() en el controlador
                    $solicitud->campo = ucfirst($solicitud->campo);
                    return $solicitud;
                }),

            // Obtener empleados nuevos en el último mes con su cargo
            'empleadosNuevos' => Trabajador::where('fecha_inicio_trabajo', '>=', now()->subMonth())
                ->select('id', 'Nombre', 'cargo_id', 'fecha_inicio_trabajo')
                ->with('cargo:id,Nombre')
                ->get()
                ->map(function ($empleado) {
                    // Formateamos la fecha en el controlador
                    $empleado->fecha_inicio_trabajo = \Carbon\Carbon::parse($empleado->fecha_inicio_trabajo)->format('d-m-Y');
                    return $empleado;
                }),

            // Obtener empresas con conteo de empleados
            'empresas' => Empresa::withCount('trabajadores')->get(),
        ];
    }


    private function validacionesVista($solicitudesPendientes, $empleadosDesvinculados, $empleadosNuevos)
    {
        return [
            'haySolicitudesPendientes' => $solicitudesPendientes->isNotEmpty(),
            'hayEmpleadosDesvinculados' => $empleadosDesvinculados->isNotEmpty(),
            'hayEmpleadosNuevos' => $empleadosNuevos->isNotEmpty(),
        ];
    }







}
