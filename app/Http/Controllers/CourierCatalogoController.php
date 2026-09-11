<?php

namespace App\Http\Controllers;

use App\Models\CourierTarifa;
use App\Services\Courier\CourierCatalogoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierCatalogoController extends Controller
{
    public function __construct(
        private readonly CourierCatalogoService $catalogo
    ) {
    }

    /*
     * Portada: conteos y salud de los catálogos, más el buscador de comunas.
     */
    public function portada(): View
    {
        return view('courier.index', [
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->input('q', ''));

        return view('courier.agentes', [
            'agentes' => $this->catalogo->agentes($buscar ?: null),
            'buscar' => $buscar,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    public function show(int $agente): View
    {
        $detalle = $this->catalogo->detalleAgente($agente);

        return view('courier.show', [
            'agente' => $detalle['agente'],
            'zonas' => $detalle['zonas'],
            'cobertura' => $detalle['cobertura'],
            'configuraciones' => $detalle['configuraciones'],
            'resumenPago' => $detalle['resumen_pago'],
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    public function comunas(Request $request): View
    {
        $buscar = trim((string) $request->input('q', ''));
        $zona = (string) $request->input('zona', '');

        if (! in_array($zona, ['RM', 'Regiones', 'sin_zona'], true)) {
            $zona = '';
        }

        return view('courier.comunas', [
            'comunas' => $this->catalogo->comunas($buscar ?: null, $zona ?: null),
            'buscar' => $buscar,
            'zona' => $zona,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    /*
     * Tarifas con una calculadora: tabla + peso → valor, usando la misma
     * regla que aplicará el cálculo mensual.
     */
    public function tarifas(Request $request): View
    {
        $tarifas = $this->catalogo->tarifas();

        $tablaCalc = $request->filled('tabla') ? (int) $request->input('tabla') : null;
        $pesoCalc = $request->filled('peso') ? (int) $request->input('peso') : null;
        $valorCalc = null;

        if ($tablaCalc !== null && $pesoCalc !== null && $pesoCalc >= 1) {
            $tarifa = $tarifas->firstWhere('numero', $tablaCalc);

            if ($tarifa instanceof CourierTarifa) {
                $valorCalc = $this->catalogo->valorPorPeso($tarifa, $pesoCalc);
            }
        }

        return view('courier.tarifas', [
            'tarifas' => $tarifas,
            'tablaCalc' => $tablaCalc,
            'pesoCalc' => $pesoCalc,
            'valorCalc' => $valorCalc,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    public function configuraciones(Request $request): View
    {
        $estado = (string) $request->input('estado', '');

        if (! in_array($estado, ['SI', 'NO', 'REVISAR'], true)) {
            $estado = '';
        }

        $agenteId = $request->filled('agente') ? (int) $request->input('agente') : null;
        $buscar = trim((string) $request->input('q', ''));

        return view('courier.configuraciones', [
            'configuraciones' => $this->catalogo->configuraciones($agenteId, $estado ?: null, $buscar ?: null),
            'agentes' => $this->catalogo->agentes(),
            'agenteSeleccionado' => $agenteId,
            'estadoSeleccionado' => $estado,
            'buscar' => $buscar,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    public function proveedores(Request $request): View
    {
        $buscar = trim((string) $request->input('q', ''));
        $tipo = trim((string) $request->input('tipo', ''));

        return view('courier.proveedores', [
            'proveedores' => $this->catalogo->proveedores($buscar ?: null, $tipo ?: null),
            'tiposDocumento' => $this->catalogo->tiposDocumento(),
            'buscar' => $buscar,
            'tipoSeleccionado' => $tipo,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }
}
