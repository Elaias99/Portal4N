<?php

namespace App\Services\Courier;

use App\Models\CourierAgentes;
use App\Models\CourierCoberturaComuna;
use App\Models\CourierConfiguracion;
use App\Models\CourierEstadoEntrega;
use App\Models\CourierPesoTransformado;
use App\Models\CourierProveedor;
use App\Models\CourierTarifa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/*
 * Consultas de sólo lectura sobre los catálogos Courier.
 *
 * Los catálogos no están versionados por período: describen cómo se
 * asigna HOY. El período vive en los datos mensuales.
 */
class CourierCatalogoService
{
    public const POR_PAGINA = 50;

    /*
     * Conteos y datos de salud de los nueve catálogos, para la portada.
     * Todo sale de la base; nada se calcula ni se estima.
     */
    public function resumen(): array
    {
        $agentesSinComunas = CourierAgentes::query()
            ->activos()
            ->whereDoesntHave('cobertura')
            ->count();

        $agentesVariosTitulares = CourierAgentes::query()
            ->activos()
            ->has('proveedores', '>', 1)
            ->count();

        return [
            'agentes' => [
                'total' => CourierAgentes::activos()->count(),
                'sin_comunas' => $agentesSinComunas,
                'varios_titulares' => $agentesVariosTitulares,
            ],
            'comunas' => [
                'total' => CourierCoberturaComuna::count(),
                'rm' => CourierCoberturaComuna::where('zona', 'RM')->count(),
                'regiones' => CourierCoberturaComuna::where('zona', 'Regiones')->count(),
                'sin_zona' => CourierCoberturaComuna::whereNull('zona')->count(),
                'con_retorno' => CourierCoberturaComuna::where('pagar_retorno', true)->count(),
            ],
            'tarifas' => [
                'total' => CourierTarifa::count(),
                'planas' => CourierTarifa::where('kilo_adicional', 0)->count(),
            ],
            'configuraciones' => [
                'total' => CourierConfiguracion::count(),
                'si' => CourierConfiguracion::where('pagar', 'SI')->count(),
                'no' => CourierConfiguracion::where('pagar', 'NO')->count(),
                'revisar' => CourierConfiguracion::where('pagar', 'REVISAR')->count(),
                'si_tabla_0' => CourierConfiguracion::where('pagar', 'SI')->where('tabla', 0)->count(),
            ],
            'proveedores' => [
                'total' => CourierProveedor::count(),
                'factura' => CourierProveedor::where('tipo_documento', 'Factura')->count(),
                'boleta' => CourierProveedor::where('tipo_documento', 'like', 'Boleta%')->count(),
                'sin_documento' => CourierProveedor::where('tipo_documento', 'Sin Documento')->count(),
                'sin_cuenta' => CourierProveedor::whereNull('nro_cuenta')->count(),
            ],
            'estados' => [
                'total' => CourierEstadoEntrega::count(),
                'descontar' => CourierEstadoEntrega::where('considerar', CourierEstadoEntrega::DESCONTAR)->count(),
            ],
            'pesos' => [
                'total' => CourierPesoTransformado::count(),
                'por_regla' => CourierPesoTransformado::where('origen', CourierPesoTransformado::ORIGEN_REGLA)->count(),
            ],
        ];
    }

    /*
     * Agentes activos con sus conteos, opcionalmente filtrados por nombre.
     */
    public function agentes(?string $buscar = null): Collection
    {
        return CourierAgentes::query()
            ->activos()
            ->when($buscar, fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"))
            ->with(['proveedores' => fn ($q) => $q->orderByDesc('principal')])
            ->withCount([
                'cobertura as comunas_count',
                'configuraciones as configuraciones_count',
                'configuraciones as pagables_count' => fn ($q) => $q->where('pagar', 'SI'),
            ])
            ->orderBy('nombre')
            ->get()
            ->map(function (CourierAgentes $agente) {
                $agente->zonas = $this->zonasDelAgente($agente->id);

                return $agente;
            });
    }

    /*
     * Zonas en las que opera un agente. Puede ser más de una:
     * "Envio externo" cubre RM y Regiones según la comuna.
     */
    public function zonasDelAgente(int $agenteId): array
    {
        return CourierCoberturaComuna::query()
            ->where('courier_agente_id', $agenteId)
            ->whereNotNull('zona')
            ->distinct()
            ->orderBy('zona')
            ->pluck('zona')
            ->all();
    }

    /*
     * Todo lo que el catálogo sabe de un agente: titulares, comunas y
     * configuraciones de pago con el nombre de su tarifa.
     */
    public function detalleAgente(int $agenteId): array
    {
        $agente = CourierAgentes::query()
            ->with(['proveedores' => fn ($q) => $q->orderByDesc('principal')])
            ->findOrFail($agenteId);

        $cobertura = CourierCoberturaComuna::query()
            ->where('courier_agente_id', $agenteId)
            ->orderBy('localidad')
            ->get();

        $tarifas = CourierTarifa::query()->pluck('nombre', 'numero');

        $configuraciones = CourierConfiguracion::query()
            ->where('courier_agente_id', $agenteId)
            ->orderBy('comerciante')
            ->orderBy('servicio')
            ->get()
            ->map(function (CourierConfiguracion $cfg) use ($tarifas) {
                $cfg->tarifa_nombre = $cfg->tabla === null ? null : $tarifas->get($cfg->tabla);

                return $cfg;
            });

        return [
            'agente' => $agente,
            'zonas' => $cobertura->pluck('zona')->filter()->unique()->values()->all(),
            'cobertura' => $cobertura,
            'configuraciones' => $configuraciones,
            'resumen_pago' => [
                'si' => $configuraciones->where('pagar', 'SI')->count(),
                'no' => $configuraciones->where('pagar', 'NO')->count(),
                'revisar' => $configuraciones->where('pagar', 'REVISAR')->count(),
            ],
        ];
    }

    /*
     * Comunas con su agente. La búsqueda usa LIKE sobre `localidad`, que
     * conserva la colación general (insensible a tildes): así "maipu"
     * encuentra "Maipú". La clave exacta sólo se usa al calcular.
     */
    public function comunas(?string $buscar = null, ?string $zona = null): LengthAwarePaginator
    {
        return CourierCoberturaComuna::query()
            ->with('agente')
            ->when($buscar, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('localidad', 'like', "%{$buscar}%")
                        ->orWhereHas('agente', fn ($a) => $a->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->when($zona === 'sin_zona', fn ($q) => $q->whereNull('zona'))
            ->when($zona && $zona !== 'sin_zona', fn ($q) => $q->where('zona', $zona))
            ->orderBy('localidad')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /*
     * Las 17 tarifas con sus tramos y precios de referencia.
     */
    public function tarifas(): Collection
    {
        return CourierTarifa::query()
            ->with(['tramos' => fn ($q) => $q->orderBy('peso')])
            ->orderBy('numero')
            ->get()
            ->map(function (CourierTarifa $tarifa) {
                $precios = $tarifa->tramos->pluck('valor', 'peso');

                $tarifa->precio_1 = $precios->get(1);
                $tarifa->precio_5 = $precios->get(5);
                $tarifa->precio_10 = $precios->get(10);
                $tarifa->precio_20 = $precios->get(20);

                return $tarifa;
            });
    }

    /*
     * Valor de una tarifa para un peso dado.
     *
     * Hasta 20 kilos se lee el tramo exacto. Sobre 20 se proyecta con el
     * kilo adicional. Verificado contra la matriz de la planilla en las
     * 16 tablas para 21, 30, 100 y 1.000 kg.
     */
    public function valorPorPeso(CourierTarifa $tarifa, int $peso): ?int
    {
        if ($peso < 1) {
            return null;
        }

        if ($peso <= 20) {
            return $tarifa->tramos->firstWhere('peso', $peso)?->valor;
        }

        $base = $tarifa->tramos->firstWhere('peso', 20)?->valor;

        if ($base === null) {
            return null;
        }

        return $base + (($peso - 20) * $tarifa->kilo_adicional);
    }

    /*
     * Configuraciones de pago, filtrables por agente, estado y texto
     * (cliente o servicio).
     */
    public function configuraciones(
        ?int $agenteId = null,
        ?string $estado = null,
        ?string $buscar = null
    ): LengthAwarePaginator {
        return CourierConfiguracion::query()
            ->with('agente')
            ->when($agenteId, fn ($q) => $q->where('courier_agente_id', $agenteId))
            ->when($estado, fn ($q) => $q->where('pagar', $estado))
            ->when($buscar, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('comerciante', 'like', "%{$buscar}%")
                        ->orWhere('servicio', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('comerciante')
            ->orderBy('servicio')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /*
     * Proveedores (a quién se le paga), filtrables por texto y tipo de documento.
     */
    public function proveedores(?string $buscar = null, ?string $tipoDocumento = null): LengthAwarePaginator
    {
        return CourierProveedor::query()
            ->when($buscar, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('operador', 'like', "%{$buscar}%")
                        ->orWhere('usuario', 'like', "%{$buscar}%")
                        ->orWhere('razon_social', 'like', "%{$buscar}%")
                        ->orWhere('rut', 'like', "%{$buscar}%");
                });
            })
            ->when($tipoDocumento, fn ($q) => $q->where('tipo_documento', $tipoDocumento))
            ->orderBy('operador')
            ->orderBy('usuario')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    public function tiposDocumento(): Collection
    {
        return CourierProveedor::query()
            ->select('tipo_documento')
            ->distinct()
            ->orderBy('tipo_documento')
            ->pluck('tipo_documento');
    }
}
