<?php

namespace App\Http\Controllers;

use App\Models\CourierPeriodo;
use App\Services\Courier\CourierCatalogoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierCatalogoController extends Controller
{
    public function __construct(
        private readonly CourierCatalogoService $catalogo
    ) {
    }

    public function index(Request $request): View
    {
        $periodos = $this->catalogo->periodos();

        $periodo = $request->filled('periodo')
            ? CourierPeriodo::where('codigo', $request->input('periodo'))->first()
            : null;

        $periodo ??= $this->catalogo->periodoVigente();

        $agentes = $periodo
            ? $this->catalogo->agentesDelPeriodo($periodo->id)
            : collect();

        return view('courier.index', [
            'periodos' => $periodos,
            'periodo' => $periodo,
            'agentes' => $agentes,
        ]);
    }

    public function show(Request $request, int $agente): View
    {
        $periodo = $request->filled('periodo')
            ? CourierPeriodo::where('codigo', $request->input('periodo'))->first()
            : null;

        $periodo ??= $this->catalogo->periodoVigente();

        abort_if($periodo === null, 404, 'No hay períodos cargados.');

        $detalle = $this->catalogo->detalleAgente($agente, $periodo->id);

        return view('courier.show', [
            'periodos' => $this->catalogo->periodos(),
            'periodo' => $periodo,
            'agente' => $detalle['agente'],
            'zonas' => $detalle['zonas'],
            'cobertura' => $detalle['cobertura'],
        ]);
    }

    public function tarifas(Request $request): View
    {
        $periodo = $request->filled('periodo')
            ? CourierPeriodo::where('codigo', $request->input('periodo'))->first()
            : null;

        $periodo ??= $this->catalogo->periodoVigente();

        $tarifas = $periodo
            ? $this->catalogo->tarifasDelPeriodo($periodo->id)
            : collect();

        return view('courier.tarifas', [
            'periodos' => $this->catalogo->periodos(),
            'periodo' => $periodo,
            'tarifas' => $tarifas,
        ]);
    }

    public function configuraciones(Request $request): View
    {
        $periodo = $request->filled('periodo')
            ? CourierPeriodo::where('codigo', $request->input('periodo'))->first()
            : null;

        $periodo ??= $this->catalogo->periodoVigente();

        $estado = $request->input('estado');

        if (! in_array($estado, ['SI', 'NO', 'REVISAR'], true)) {
            $estado = null;
        }

        $configuraciones = $periodo
            ? $this->catalogo->configuracionesDelPeriodo(
                $periodo->id,
                $request->filled('agente') ? (int) $request->input('agente') : null,
                $estado
            )
            : collect();

        return view('courier.configuraciones', [
            'periodos' => $this->catalogo->periodos(),
            'periodo' => $periodo,
            'agentes' => $periodo
                ? $this->catalogo->agentesDelPeriodo($periodo->id)
                : collect(),
            'configuraciones' => $configuraciones,
            'agenteSeleccionado' => $request->input('agente'),
            'estadoSeleccionado' => $estado,
        ]);
    }


}