<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionLiquidacionDetalle;
use App\Models\SuscripcionComisionMensual;
use App\Services\Calendar\ChileCalendarService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Suscripciones\SuscripcionLiquidacionResumenService;
use App\Services\Suscripciones\SuscripcionPrefacturaZipService;
use App\Services\Suscripciones\SuscripcionPrefacturaAgrupacionService;
use App\Services\Suscripciones\SuscripcionGeneracionMensualService;
use App\Services\Suscripciones\SuscripcionPrefacturaOcService;
use App\Services\Suscripciones\SuscripcionAjusteMensualService;
use Illuminate\Http\Request;

class SuscripcionLiquidacionDetalleController extends Controller
{




    public function index( Request $request, SuscripcionLiquidacionResumenService $resumenService, SuscripcionAjusteMensualService $ajusteMensualService) 
    {
        $proveedor = trim((string) $request->input('proveedor'));
        $rut = trim((string) $request->input('rut'));
        $tipo = trim((string) $request->input('tipo'));
        $anio = $request->input('anio');
        $mes = $request->input('mes');

        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        $tiposDocumento = \App\Models\SuscripcionProveedor::query()
            ->whereNotNull('tipo')
            ->where('tipo', '<>', '')
            ->select('tipo')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo');

        $query = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
        ]);

        if ($anio) {
            $query->where('anio', $anio);
        }

        if ($mes) {
            $query->where('mes', $mes);
        }

        /*
        * Los filtros por proveedor, rut y tipo se aplican después de traer los detalles,
        * porque ahora pueden depender de un ajuste mensual.
        *
        * Ejemplo:
        * - La asignación base puede ser de José Luis.
        * - Pero en mayo puede facturar Manuel Hernández.
        */
        $detallesFiltrados = $query
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('codigo')
            ->get()
            ->filter(function ($detalle) use ($proveedor, $rut, $tipo, $ajusteMensualService) {
                $proveedorPrefactura = $ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
                $cobranzaCompra = $proveedorPrefactura?->cobranzaCompra;

                $tipoDocumento = $ajusteMensualService->tipoDocumentoParaDetalle($detalle)
                    ?? $proveedorPrefactura?->tipo;

                if ($proveedor !== '') {
                    $razonSocial = mb_strtoupper(trim((string) $cobranzaCompra?->razon_social));
                    $proveedorFiltro = mb_strtoupper($proveedor);

                    if (!str_contains($razonSocial, $proveedorFiltro)) {
                        return false;
                    }
                }

                if ($rut !== '') {
                    $rutCliente = mb_strtoupper(trim((string) $cobranzaCompra?->rut_cliente));
                    $rutFiltro = mb_strtoupper($rut);

                    if (!str_contains($rutCliente, $rutFiltro)) {
                        return false;
                    }
                }

                if ($tipo !== '') {
                    $tipoDocumento = mb_strtoupper(trim((string) $tipoDocumento));
                    $tipoFiltro = mb_strtoupper(trim($tipo));

                    if ($tipoDocumento !== $tipoFiltro) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        /*
        * El index debe seguir mostrando una sola fila por proveedor/año/mes.
        * Pero ahora el proveedor puede venir desde el ajuste mensual.
        */
        $prefacturas = $detallesFiltrados
            ->groupBy(function ($detalle) use ($ajusteMensualService) {
                $proveedorPrefactura = $ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
                $suscripcionProveedorId = $proveedorPrefactura?->id ?? 'sin_proveedor';

                return $suscripcionProveedorId . '_' . $detalle->anio . '_' . $detalle->mes;
            })
            ->map(function ($items) use ($resumenService, $meses, $ajusteMensualService) {
                $items = $items->values();
                $detalleBase = $items->first();

                $proveedor = $ajusteMensualService->proveedorFacturacionParaDetalle($detalleBase);
                $cobranzaCompra = $proveedor?->cobranzaCompra;

                $calculosDetalle = $resumenService->calcularPorDetalles($items);
                $calculoBase = $calculosDetalle->first();

                return [
                    'detalle_id' => $detalleBase->id,
                    'suscripcion_proveedor_id' => $proveedor?->id,
                    'anio' => $detalleBase->anio,
                    'mes' => $detalleBase->mes,
                    'mes_nombre' => $meses[$detalleBase->mes] ?? $detalleBase->mes,

                    'proveedor' => $cobranzaCompra?->razon_social ?? '—',
                    'rut' => $cobranzaCompra?->rut_cliente ?? '—',

                    'tipo' => $calculoBase['tipo'] ?? $proveedor?->tipo ?? '—',
                    'detalle_documento' => $calculoBase['detalle_documento'] ?? $proveedor?->detalle_documento ?? 'Neto/Bruto',
                    'detalle_impuesto' => $calculoBase['detalle_impuesto'] ?? $proveedor?->detalle_impuesto ?? 'Impuesto',
                    'final' => $calculoBase['final'] ?? $proveedor?->final ?? 'Final',

                    'cantidad_lineas' => $items->count(),

                    'neto_bruto' => $items->sum('total'),
                    'total_impuesto' => $calculosDetalle->sum('total_impuesto'),
                    'total_final' => $calculosDetalle->sum('liquido'),
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['anio'] !== $b['anio']) {
                    return $b['anio'] <=> $a['anio'];
                }

                if ($a['mes'] !== $b['mes']) {
                    return $b['mes'] <=> $a['mes'];
                }

                return strcmp($a['proveedor'], $b['proveedor']);
            })
            ->values();

        $resumenPorTipo = [
            'BOLETA' => [
                'label' => 'Boletas',
                'cantidad' => 0,
                'neto_bruto' => 0,
                'total_impuesto' => 0,
                'total_final' => 0,
            ],
            'FACTURA' => [
                'label' => 'Facturas',
                'cantidad' => 0,
                'neto_bruto' => 0,
                'total_impuesto' => 0,
                'total_final' => 0,
            ],
            'DOCUMENTO' => [
                'label' => 'Documentos',
                'cantidad' => 0,
                'neto_bruto' => 0,
                'total_impuesto' => 0,
                'total_final' => 0,
            ],
            'TOTAL' => [
                'label' => 'Total general',
                'cantidad' => 0,
                'neto_bruto' => 0,
                'total_impuesto' => 0,
                'total_final' => 0,
            ],
        ];

        foreach ($prefacturas as $prefactura) {
            $tipoDocumento = mb_strtoupper(trim((string) ($prefactura['tipo'] ?? '')));

            if (str_contains($tipoDocumento, 'BOLETA')) {
                $clave = 'BOLETA';
            } elseif (str_contains($tipoDocumento, 'FACTURA')) {
                $clave = 'FACTURA';
            } elseif (str_contains($tipoDocumento, 'DOCUMENTO')) {
                $clave = 'DOCUMENTO';
            } else {
                continue;
            }

            $resumenPorTipo[$clave]['cantidad']++;
            $resumenPorTipo[$clave]['neto_bruto'] += $prefactura['neto_bruto'];
            $resumenPorTipo[$clave]['total_impuesto'] += $prefactura['total_impuesto'];
            $resumenPorTipo[$clave]['total_final'] += $prefactura['total_final'];

            $resumenPorTipo['TOTAL']['cantidad']++;
            $resumenPorTipo['TOTAL']['neto_bruto'] += $prefactura['neto_bruto'];
            $resumenPorTipo['TOTAL']['total_impuesto'] += $prefactura['total_impuesto'];
            $resumenPorTipo['TOTAL']['total_final'] += $prefactura['total_final'];
        }

        $totalPeriodo = $resumenPorTipo['TOTAL']['neto_bruto'];
        $cantidadRegistros = $prefacturas->count();

        $filtrosActivos = collect([
            $proveedor,
            $rut,
            $tipo,
            $anio,
            $mes,
        ])->filter(fn ($value) => $value !== null && $value !== '')->count();

        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;

        $prefacturasPaginadas = new \Illuminate\Pagination\LengthAwarePaginator(
            $prefacturas->slice(($page - 1) * $perPage, $perPage)->values(),
            $prefacturas->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('suscripciones.liquidacion_detalles.index', [
            'prefacturas' => $prefacturasPaginadas,
            'proveedor' => $proveedor,
            'rut' => $rut,
            'tipo' => $tipo,
            'anio' => $anio,
            'mes' => $mes,
            'meses' => $meses,
            'tiposDocumento' => $tiposDocumento,
            'totalPeriodo' => $totalPeriodo,
            'cantidadRegistros' => $cantidadRegistros,
            'filtrosActivos' => $filtrosActivos,
            'resumenPorTipo' => $resumenPorTipo,
        ]);
    }





    public function create()
    {
        $asignaciones = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
        ->orderBy('codigo')
        ->get();

        return view('suscripciones.liquidacion_detalles.create', compact('asignaciones'));
    }

    public function store(Request $request, ChileCalendarService $calendar)
    {
        $request->validate([
            'suscripcion_asignacion_id' => 'required|exists:suscripcion_asignaciones,id',
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'q_inasistencia' => 'nullable|integer|min:0',
        ]);

        $asignacion = Asignaciones::findOrFail($request->suscripcion_asignacion_id);

        $anio = (int) $request->anio;
        $mes = (int) $request->mes;
        $qInasistencia = (int) ($request->q_inasistencia ?? 0);

        $calculo = $this->calcularDetalleMensual(
            $asignacion,
            $anio,
            $mes,
            $qInasistencia
        );

        SuscripcionLiquidacionDetalle::create([
            'suscripcion_asignacion_id' => $asignacion->id,
            'anio' => $anio,
            'mes' => $mes,
            'codigo' => $asignacion->codigo,
            'costo' => $asignacion->costo,
            'q_calendario' => $calculo['q_calendario'],
            'q_inasistencia' => $calculo['q_inasistencia'],
            'cantidad' => $calculo['cantidad'],
            'total' => $calculo['total'],
        ]);

        return redirect()
            ->route('suscripciones.liquidacion-detalles.create')
            ->with('success', 'Detalle mensual calculado y guardado correctamente.');
    }

    public function edit(SuscripcionLiquidacionDetalle $detalle)
    {
        $detalle->load([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
        ]);

        return view('suscripciones.liquidacion_detalles.edit', compact('detalle'));
    }

    public function update(Request $request, SuscripcionLiquidacionDetalle $detalle)
    {
        $request->validate([
            'q_inasistencia' => 'required|integer|min:0',
        ]);

        $detalle->load('asignacion');

        if (!$detalle->asignacion) {
            return back()
                ->withErrors([
                    'asignacion' => 'No se encontró la asignación asociada a este detalle.',
                ])
                ->withInput();
        }

        $qInasistencia = (int) $request->q_inasistencia;



        if (
            !$this->esCodigoValorFijo((string) $detalle->asignacion->codigo)
            && !$this->esAsignacionOPV($detalle->asignacion)
            && $qInasistencia > $detalle->q_calendario
        ) {





            return back()
                ->withErrors([
                    'q_inasistencia' => 'La inasistencia no puede ser mayor al Q calendario.',
                ])
                ->withInput();
        }

        $calculo = $this->calcularDetalleMensual(
            $detalle->asignacion,
            (int) $detalle->anio,
            (int) $detalle->mes,
            $qInasistencia
        );

        $detalle->update([
            'q_calendario' => $calculo['q_calendario'],
            'q_inasistencia' => $calculo['q_inasistencia'],
            'cantidad' => $calculo['cantidad'],
            'total' => $calculo['total'],
        ]);

        return redirect()
            ->route('suscripciones.liquidacion-detalles.index')
            ->with('success', 'Inasistencia actualizada y total recalculado correctamente.');
    }





    public function show( SuscripcionLiquidacionDetalle $detalle, SuscripcionLiquidacionResumenService $resumenService, SuscripcionPrefacturaAgrupacionService $agrupacionService, SuscripcionAjusteMensualService $ajusteMensualService) 
    {
        $detalle->load([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
        ]);

        /*
        * Ahora el proveedor de la pre-factura puede venir desde el ajuste mensual.
        * Ejemplo:
        * - Asignación base: José Luis
        * - Mayo 2026: proveedor facturación Manuel Hernández
        */
        $proveedorPrefactura = $ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
        $suscripcionProveedorId = $proveedorPrefactura?->id;

        if (!$suscripcionProveedorId) {
            abort(404, 'No se encontró el proveedor de suscripción asociado.');
        }

        $grupoPrefactura = $agrupacionService->grupoDesdeDetalle($detalle);
        $grupoPrefacturaLabel = $agrupacionService->etiquetaGrupo($grupoPrefactura);

        /*
        * Antes se filtraba por asignacion.suscripcion_proveedor_id.
        * Ahora se traen los detalles del periodo y se filtran por proveedor efectivo
        * de pre-factura, considerando ajustes mensuales.
        */
        $detallesProveedor = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
            'asignacion.opvPuntos',
            'asignacion.cantidadesMensuales',
        ])
            ->where('anio', $detalle->anio)
            ->where('mes', $detalle->mes)
            ->orderBy('codigo')
            ->get()
            ->filter(function ($item) use ($suscripcionProveedorId, $ajusteMensualService) {
                $proveedorItem = $ajusteMensualService->proveedorFacturacionParaDetalle($item);

                return (int) $proveedorItem?->id === (int) $suscripcionProveedorId;
            })
            ->values();

        $gruposPrefactura = $detallesProveedor
            ->groupBy(function ($item) use ($agrupacionService) {
                $grupo = $agrupacionService->grupoDesdeDetalle($item);

                return $agrupacionService->claveGrupo($grupo);
            })
            ->map(function ($items) use ($resumenService, $agrupacionService) {
                $items = $items->values();
                $detalleBase = $items->first();

                $grupo = $agrupacionService->grupoDesdeDetalle($detalleBase);
                $grupoLabel = $agrupacionService->etiquetaGrupo($grupo);
                $calculosGrupo = $resumenService->calcularPorDetalles($items);

                return [
                    'label' => $grupoLabel,
                    'es_general' => mb_strtoupper($grupoLabel) === 'GENERAL',
                    'detalle_id' => $detalleBase->id,
                    'items' => $items,
                    'calculos' => $calculosGrupo,
                    'total_bruto' => $items->sum('total'),
                    'total_impuesto' => $calculosGrupo->sum('total_impuesto'),
                    'total_liquido' => $calculosGrupo->sum('liquido'),
                ];
            })
            ->sortBy(fn ($grupo) => $grupo['es_general'] ? 0 : 1)
            ->values();

        $calculosDetalle = $resumenService->calcularPorDetalles($detallesProveedor);

        /*
        * Encabezado de la pre-factura.
        * Usa proveedor efectivo de facturación.
        */
        $proveedor = $proveedorPrefactura;
        $cobranzaCompra = $proveedor?->cobranzaCompra;

        $totalBruto = $detallesProveedor->sum('total');
        $totalImpuesto = $calculosDetalle->sum('total_impuesto');
        $totalLiquido = $calculosDetalle->sum('liquido');

        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        /*
        * Estado anual del proveedor efectivo.
        * Esto permite que mayo aparezca bajo Manuel si existe ajuste,
        * y que abril siga bajo José Luis si no tiene ajuste.
        */
        $detallesAnioProveedor = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
            'asignacion.cantidadesMensuales',
        ])
            ->where('anio', $detalle->anio)
            ->orderBy('mes')
            ->orderBy('codigo')
            ->get()
            ->filter(function ($item) use ($suscripcionProveedorId, $ajusteMensualService) {
                $proveedorItem = $ajusteMensualService->proveedorFacturacionParaDetalle($item);

                return (int) $proveedorItem?->id === (int) $suscripcionProveedorId;
            })
            ->values();

        $prefacturasProveedorAnio = $detallesAnioProveedor
            ->groupBy('mes')
            ->map(function ($items) use ($resumenService, $meses) {
                $items = $items->values();
                $detalleBase = $items->first();

                $calculosMes = $resumenService->calcularPorDetalles($items);

                return [
                    'detalle_id' => $detalleBase->id,
                    'anio' => $detalleBase->anio,
                    'mes' => (int) $detalleBase->mes,
                    'mes_nombre' => $meses[(int) $detalleBase->mes] ?? $detalleBase->mes,
                    'cantidad_lineas' => $items->count(),
                    'total_bruto' => $items->sum('total'),
                    'total_impuesto' => $calculosMes->sum('total_impuesto'),
                    'total_final' => $calculosMes->sum('liquido'),
                ];
            });

        $estadoPrefacturas = collect($meses)
            ->map(function ($nombreMes, $numeroMes) use ($prefacturasProveedorAnio, $detalle) {
                $prefacturaMes = $prefacturasProveedorAnio->get($numeroMes);

                return [
                    'mes' => $numeroMes,
                    'mes_nombre' => $nombreMes,
                    'generada' => (bool) $prefacturaMes,
                    'es_actual' => (int) $detalle->mes === (int) $numeroMes,
                    'detalle_id' => $prefacturaMes['detalle_id'] ?? null,
                    'cantidad_lineas' => $prefacturaMes['cantidad_lineas'] ?? 0,
                    'total_final' => $prefacturaMes['total_final'] ?? 0,
                ];
            })
            ->values();

        /*
        * OPV pendientes se revisan sobre el proveedor efectivo de la pre-factura.
        */
        $opvPendientes = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
            'opvPuntos',
        ])
            ->where('suscripcion_proveedor_id', $suscripcionProveedorId)
            ->where(function ($query) {
                $query->whereRaw("UPPER(TRIM(codigo)) = 'OPV'")
                    ->orWhereRaw("UPPER(TRIM(codigo)) LIKE '%.OPV'")
                    ->orWhereRaw("UPPER(TRIM(servicio)) = 'OPV'")
                    ->orWhereRaw("UPPER(TRIM(origen_gasto)) = 'OPV'");
            })
            ->whereDoesntHave('opvPuntos')
            ->orderBy('punto_1')
            ->get();

        return view('suscripciones.liquidacion_detalles.show', compact(
            'detalle',
            'detallesProveedor',
            'calculosDetalle',
            'proveedor',
            'cobranzaCompra',
            'totalBruto',
            'totalImpuesto',
            'totalLiquido',
            'meses',
            'estadoPrefacturas',
            'opvPendientes',
            'grupoPrefactura',
            'grupoPrefacturaLabel',
            'gruposPrefactura'
        ));
    }




    public function pdf(SuscripcionLiquidacionDetalle $detalle, SuscripcionLiquidacionResumenService $resumenService, SuscripcionPrefacturaAgrupacionService $agrupacionService, SuscripcionPrefacturaOcService $ocService, SuscripcionAjusteMensualService $ajusteMensualService) 
    {
        $detalle->load([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
        ]);

        /*
        * El PDF debe usar el mismo proveedor efectivo que la vista show.
        * Ejemplo:
        * - Asignación base LOTA: Carlos Calfucura
        * - Junio 2026: ajuste FACTURACION a Victor Cornejo
        * - El PDF debe salir a nombre de Victor Cornejo.
        */
        $proveedorPrefactura = $ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
        $suscripcionProveedorId = $proveedorPrefactura?->id;

        if (!$suscripcionProveedorId) {
            abort(404, 'No se encontró el proveedor de suscripción asociado.');
        }

        $grupoPrefactura = $agrupacionService->grupoDesdeDetalle($detalle);
        $grupoPrefacturaLabel = $agrupacionService->etiquetaGrupo($grupoPrefactura);

        /*
        * Igual que en show(), se traen los detalles del periodo y luego se filtran
        * por proveedor efectivo, porque puede existir cambio de facturación mensual.
        */
        $detallesProveedor = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
            'asignacion.cantidadesMensuales',
        ])
            ->where('anio', $detalle->anio)
            ->where('mes', $detalle->mes)
            ->orderBy('codigo')
            ->get()
            ->filter(function ($item) use (
                $suscripcionProveedorId,
                $grupoPrefactura,
                $agrupacionService,
                $ajusteMensualService
            ) {
                $proveedorItem = $ajusteMensualService->proveedorFacturacionParaDetalle($item);

                if ((int) $proveedorItem?->id !== (int) $suscripcionProveedorId) {
                    return false;
                }

                $grupoItem = $agrupacionService->grupoDesdeDetalle($item);

                return $agrupacionService->claveGrupo($grupoItem)
                    === $agrupacionService->claveGrupo($grupoPrefactura);
            })
            ->values();

        if ($detallesProveedor->isEmpty()) {
            abort(404, 'No se encontraron detalles para la pre-factura solicitada.');
        }

        $calculosDetalle = $resumenService->calcularPorDetalles($detallesProveedor);

        /*
        * Encabezado, datos de pago, tipo documento y nombre de archivo:
        * todos deben salir desde el proveedor efectivo.
        */
        $proveedor = $proveedorPrefactura;
        $cobranzaCompra = $proveedor?->cobranzaCompra;

        $ocPrefactura = $ocService->generarOC(
            (int) $detalle->anio,
            (int) $detalle->mes,
            (int) $suscripcionProveedorId
        );

        $totalBruto = $detallesProveedor->sum('total');
        $totalImpuesto = $calculosDetalle->sum('total_impuesto');
        $totalLiquido = $calculosDetalle->sum('liquido');

        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        $nombreProveedor = $cobranzaCompra?->razon_social ?? 'Proveedor';
        $tipo = $proveedor?->tipo ?? 'DOC';

        $nombreArchivoProveedor = str_replace(
            ' ',
            '_',
            preg_replace('/[^A-Za-z0-9\s]/', '', $nombreProveedor)
        );

        $grupoArchivo = '';

        if ($grupoPrefactura !== null) {
            $grupoArchivo = '_' . str_replace(
                ' ',
                '_',
                preg_replace('/[^A-Za-z0-9\s._-]/', '', $grupoPrefacturaLabel)
            );
        }

        $nombreArchivo = "PreFactura_Susc_{$tipo}_{$nombreArchivoProveedor}{$grupoArchivo}_{$detalle->anio}_{$detalle->mes}.pdf";

        $pdf = Pdf::loadView('suscripciones.liquidacion_detalles.pdf', [
            'detalle' => $detalle,
            'detallesProveedor' => $detallesProveedor,
            'calculosDetalle' => $calculosDetalle,
            'proveedor' => $proveedor,
            'cobranzaCompra' => $cobranzaCompra,
            'totalBruto' => $totalBruto,
            'totalImpuesto' => $totalImpuesto,
            'totalLiquido' => $totalLiquido,
            'meses' => $meses,
            'grupoPrefactura' => $grupoPrefactura,
            'grupoPrefacturaLabel' => $grupoPrefacturaLabel,
            'ocPrefactura' => $ocPrefactura,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream($nombreArchivo);
    }




    public function pdfMasivo(Request $request, SuscripcionPrefacturaZipService $zipService, SuscripcionAjusteMensualService $ajusteMensualService) 
    {
        $request->validate([
            'anio_pdf' => 'required|integer|min:2020|max:2100',
            'mes_pdf' => 'required|integer|min:1|max:12',
            'proveedor_pdf' => 'nullable|string',
            'rut_pdf' => 'nullable|string',
            'tipo_pdf' => 'nullable|string',
        ]);

        $anio = (int) $request->anio_pdf;
        $mes = (int) $request->mes_pdf;
        $proveedorFiltro = trim((string) $request->proveedor_pdf);
        $rutFiltro = trim((string) $request->rut_pdf);
        $tipoFiltro = trim((string) $request->tipo_pdf);

        /*
        * Importante:
        * No filtramos por proveedor base en SQL, porque una línea puede pertenecer
        * a otro proveedor efectivo por ajuste mensual.
        */
        $detallesBase = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
            'asignacion.cantidadesMensuales',
        ])
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->orderBy('codigo')
            ->get()
            ->filter(function ($detalle) use (
                $proveedorFiltro,
                $rutFiltro,
                $tipoFiltro,
                $ajusteMensualService
            ) {
                $proveedorEfectivo = $ajusteMensualService->proveedorFacturacionParaDetalle($detalle);
                $cobranzaCompra = $proveedorEfectivo?->cobranzaCompra;

                if (!$proveedorEfectivo) {
                    return false;
                }

                if ($proveedorFiltro !== '') {
                    $razonSocial = mb_strtoupper(trim((string) $cobranzaCompra?->razon_social));
                    $filtro = mb_strtoupper($proveedorFiltro);

                    if (!str_contains($razonSocial, $filtro)) {
                        return false;
                    }
                }

                if ($rutFiltro !== '') {
                    $rutCliente = mb_strtoupper(trim((string) $cobranzaCompra?->rut_cliente));
                    $filtro = mb_strtoupper($rutFiltro);

                    if (!str_contains($rutCliente, $filtro)) {
                        return false;
                    }
                }

                if ($tipoFiltro !== '') {
                    $tipoDocumento = mb_strtoupper(trim((string) (
                        $ajusteMensualService->tipoDocumentoParaDetalle($detalle)
                        ?? $proveedorEfectivo?->tipo
                    )));

                    $filtro = mb_strtoupper(trim($tipoFiltro));

                    if ($tipoDocumento !== $filtro) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        if ($detallesBase->isEmpty()) {
            return back()->withErrors([
                'pdf_masivo' => 'No existen detalles para generar PDFs con los filtros seleccionados.',
            ]);
        }

        try {
            $resultado = $zipService->generarDesdeDetalles($detallesBase, $anio, $mes);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'pdf_masivo' => $e->getMessage(),
            ]);
        }

        return response()
            ->download($resultado['zip_path'], $resultado['zip_file_name']);
    }



    public function generarMes(Request $request, SuscripcionGeneracionMensualService $generacionMensualService)
    {
        $request->validate([
            'anio_generar' => 'required|integer|min:2020|max:2100',
            'mes_generar' => 'required|integer|min:1|max:12',
        ]);

        $anio = (int) $request->anio_generar;
        $mes = (int) $request->mes_generar;
        $proveedorActual = trim((string) $request->input('proveedor_actual'));

        $resultado = $generacionMensualService->generar($anio, $mes);

        $params = [
            'anio' => $anio,
            'mes' => $mes,
        ];

        if ($proveedorActual !== '') {
            $params['proveedor'] = $proveedorActual;
        }

        $mensaje = "Mes generado correctamente. Creados: {$resultado['creados']}.";

        if ($resultado['comisiones_creadas'] > 0) {
            $mensaje .= " Comisiones agregadas: {$resultado['comisiones_creadas']}.";
        }

        if ($resultado['duplicados'] > 0) {
            $mensaje .= " Registros ya existentes no duplicados: {$resultado['duplicados']}.";
        }

        if ($resultado['comisiones_duplicadas'] > 0) {
            $mensaje .= " Comisiones ya existentes no duplicadas: {$resultado['comisiones_duplicadas']}.";
        }

        if ($resultado['opv_sin_rutas']->isNotEmpty()) {
            $mensaje .= ' No se generaron las siguientes rutas OPV porque no tienen locales OPV asignados: ';
            $mensaje .= $resultado['opv_sin_rutas']->unique()->implode('; ') . '.';
        }

        return redirect()
            ->route('suscripciones.liquidacion-detalles.index', $params)
            ->with('success', $mensaje);
    }




    public function opvPuntos(Asignaciones $asignacion)
    {
        $asignacion->load([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
            'opvPuntos',
        ]);

        if (!$this->esAsignacionOPV($asignacion)) {
            abort(404, 'La asignación seleccionada no corresponde a una ruta OPV.');
        }

        $opvPuntos = $asignacion->opvPuntos()
            ->orderBy('ruta_nombre')
            ->orderBy('local')
            ->get();

        return view('suscripciones.liquidacion_detalles.opv_puntos', compact(
            'asignacion',
            'opvPuntos'
        ));
    }






    private function calcularDetalleMensual(Asignaciones $asignacion, int $anio, int $mes, int $qInasistencia = 0): array
    {
        $codigo = (string) $asignacion->codigo;
        $costo = (int) $asignacion->costo;

        if ($this->esCodigoValorFijo($codigo)) {
            return [
                'q_calendario' => 1,
                'q_inasistencia' => 0,
                'cantidad' => 1,
                'total' => $costo,
            ];
        }

        if ($this->esAsignacionOPV($asignacion)) {
            $qCalendario = $this->contarFinesDeSemanaDelMes($anio, $mes);

            $cantidadPuntos = $asignacion->opvPuntos()->count();

            $cantidad = $qCalendario * $cantidadPuntos;

            return [
                'q_calendario' => $qCalendario,
                'q_inasistencia' => 0,
                'cantidad' => $cantidad,
                'total' => $costo * $cantidad,
            ];
        }

        $qCalendario = $this->contarFinesDeSemanaDelMes($anio, $mes);

        $qInasistencia = max((int) $qInasistencia, 0);

        if ($qInasistencia > $qCalendario) {
            $qInasistencia = $qCalendario;
        }

        $cantidad = max($qCalendario - $qInasistencia, 0);

        return [
            'q_calendario' => $qCalendario,
            'q_inasistencia' => $qInasistencia,
            'cantidad' => $cantidad,
            'total' => $costo * $cantidad,
        ];
    }





    private function esCodigoValorFijo(string $codigo): bool
    {
        return str_ends_with(mb_strtoupper(trim($codigo)), '.COM');
    }






    private function contarFinesDeSemanaDelMes(int $anio, int $mes): int
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $fin = $inicio->copy()->endOfMonth();

        $periodo = CarbonPeriod::create($inicio, $fin);

        $total = 0;

        foreach ($periodo as $fecha) {
            if ($fecha->isSaturday() || $fecha->isSunday()) {
                $total++;
            }
        }

        return $total;
    }

    private function esAsignacionOPV(Asignaciones $asignacion): bool
    {
        $codigo = mb_strtoupper(trim((string) $asignacion->codigo));
        $servicio = mb_strtoupper(trim((string) $asignacion->servicio));
        $origenGasto = mb_strtoupper(trim((string) $asignacion->origen_gasto));

        return $codigo === 'OPV'
            || str_ends_with($codigo, '.OPV')
            || $servicio === 'OPV'
            || $origenGasto === 'OPV';
    }






}