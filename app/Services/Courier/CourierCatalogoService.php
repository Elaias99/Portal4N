<?php

namespace App\Services\Courier;

use App\Models\CourierAgentes;
use App\Models\CourierCoberturaComuna;
use App\Models\CourierPeriodo;
use App\Models\CourierTarifa;
use App\Models\CourierConfiguracion;
use Illuminate\Support\Collection;

class CourierCatalogoService
{
    /*
     * Períodos disponibles para el selector, del más reciente
     * al más antiguo.
     */
    public function periodos(): Collection
    {
        return CourierPeriodo::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();
    }

    /*
     * Período por defecto: el más reciente cargado.
     */
    public function periodoVigente(): ?CourierPeriodo
    {
        return CourierPeriodo::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->first();
    }

    /*
     * Agentes activos con la cantidad de comunas que cubren en el
     * período indicado, y sus titulares.
     *
     * La zona no vive en el agente sino en la cobertura, porque un
     * agente puede operar en más de una zona.
     */
    public function agentesDelPeriodo(int $periodoId): Collection
    {
        return CourierAgentes::query()
            ->activos()
            ->with([
                'proveedores' => fn ($q) => $q->orderByDesc('principal'),
            ])
            ->withCount([
                'cobertura as comunas_count' => fn ($q) =>
                    $q->where('courier_periodo_id', $periodoId),
            ])
            ->orderBy('nombre')
            ->get()
            ->map(function (CourierAgentes $agente) use ($periodoId) {
                $agente->zonas = $this->zonasDelAgente($agente->id, $periodoId);

                return $agente;
            });
    }

    /*
     * Zonas en las que opera un agente durante el período.
     * Devuelve más de una en casos como "Envio externo".
     */
    public function zonasDelAgente(int $agenteId, int $periodoId): array
    {
        return CourierCoberturaComuna::query()
            ->where('courier_periodo_id', $periodoId)
            ->where('courier_agente_id', $agenteId)
            ->whereNotNull('zona')
            ->distinct()
            ->orderBy('zona')
            ->pluck('zona')
            ->all();
    }

    /*
     * Detalle de un agente: sus titulares y el listado completo de
     * comunas que cubre en el período.
     */
    public function detalleAgente(int $agenteId, int $periodoId): array
    {
        $agente = CourierAgentes::query()
            ->with([
                'proveedores' => fn ($q) => $q->orderByDesc('principal'),
            ])
            ->findOrFail($agenteId);

        $cobertura = CourierCoberturaComuna::query()
            ->where('courier_periodo_id', $periodoId)
            ->where('courier_agente_id', $agenteId)
            ->orderBy('localidad')
            ->get();

        return [
            'agente' => $agente,
            'zonas' => $cobertura->pluck('zona')->filter()->unique()->values()->all(),
            'cobertura' => $cobertura,
        ];
    }


        /*
     * Las 17 tarifas del periodo con su precio a pesos de referencia.
     */
    public function tarifasDelPeriodo(int $periodoId): Collection
    {
        return CourierTarifa::query()
            ->where('courier_periodo_id', $periodoId)
            ->with([
                'tramos' => fn ($q) => $q->orderBy('peso'),
            ])
            ->orderBy('numero')
            ->get()
            ->map(function (CourierTarifa $tarifa) {
                $precios = $tarifa->tramos->pluck('valor', 'peso');

                $tarifa->precio_1  = $precios->get(1);
                $tarifa->precio_5  = $precios->get(5);
                $tarifa->precio_10 = $precios->get(10);
                $tarifa->precio_20 = $precios->get(20);

                return $tarifa;
            });
    }

    /*
     * Valor de una tarifa para un peso dado.
     *
     * Hasta 20 kilos se lee el tramo exacto. Sobre 20 se proyecta con
     * el kilo adicional, igual que hace la matriz de la planilla.
     */
    public function valorPorPeso(CourierTarifa $tarifa, int $peso): ?int
    {
        if ($peso < 1) {
            return null;
        }

        if ($peso <= 20) {
            return $tarifa->tramos
                ->firstWhere('peso', $peso)
                ?->valor;
        }

        $base = $tarifa->tramos->firstWhere('peso', 20)?->valor;

        if ($base === null) {
            return null;
        }

        return $base + (($peso - 20) * $tarifa->kilo_adicional);
    }


        /*
     * Configuraciones de pago del periodo: que tarifa y que estado
     * le corresponde a cada combinacion agente + comerciante + servicio.
     */
    public function configuracionesDelPeriodo(
        int $periodoId,
        ?int $agenteId = null,
        ?string $estado = null
    ): Collection {
        return CourierConfiguracion::query()
            ->where('courier_periodo_id', $periodoId)
            ->when($agenteId, fn ($q) => $q->where('courier_agente_id', $agenteId))
            ->when($estado, fn ($q) => $q->where('pagar', $estado))
            ->with('agente')
            ->orderBy('comerciante')
            ->orderBy('servicio')
            ->get();
    }








}