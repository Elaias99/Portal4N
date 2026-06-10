<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionLiquidacionDetalle;
use App\Services\Calendar\ChileCalendarService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Suscripciones\SuscripcionLiquidacionResumenService;
use App\Services\Suscripciones\SuscripcionPrefacturaZipService;
use Illuminate\Http\Request;
use App\Models\SuscripcionOPVPuntos;
use ZipArchive;

class SuscripcionLiquidacionDetalleController extends Controller
{




    public function index(Request $request, SuscripcionLiquidacionResumenService $resumenService)
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

        if ($proveedor !== '') {
            $query->whereHas('asignacion.suscripcionProveedor.cobranzaCompra', function ($q) use ($proveedor) {
                $q->where('razon_social', 'like', '%' . $proveedor . '%');
            });
        }

        if ($rut !== '') {
            $query->whereHas('asignacion.suscripcionProveedor.cobranzaCompra', function ($q) use ($rut) {
                $q->where('rut_cliente', 'like', '%' . $rut . '%');
            });
        }

        if ($tipo !== '') {
            $query->whereHas('asignacion.suscripcionProveedor', function ($q) use ($tipo) {
                $q->where('tipo', $tipo);
            });
        }

        if ($anio) {
            $query->where('anio', $anio);
        }

        if ($mes) {
            $query->where('mes', $mes);
        }

        $detallesFiltrados = $query
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('codigo')
            ->get();

        $prefacturas = $detallesFiltrados
            ->groupBy(function ($detalle) {
                $suscripcionProveedorId = $detalle->asignacion?->suscripcion_proveedor_id ?? 'sin_proveedor';

                return $suscripcionProveedorId . '_' . $detalle->anio . '_' . $detalle->mes;
            })
            ->map(function ($items) use ($resumenService, $meses) {
                $detalleBase = $items->first();

                $proveedor = $detalleBase->asignacion?->suscripcionProveedor;
                $cobranzaCompra = $proveedor?->cobranzaCompra;

                $calculosDetalle = $resumenService->calcularPorDetalles($items);

                return [
                    'detalle_id' => $detalleBase->id,
                    'suscripcion_proveedor_id' => $detalleBase->asignacion?->suscripcion_proveedor_id,
                    'anio' => $detalleBase->anio,
                    'mes' => $detalleBase->mes,
                    'mes_nombre' => $meses[$detalleBase->mes] ?? $detalleBase->mes,

                    'proveedor' => $cobranzaCompra?->razon_social ?? '—',
                    'rut' => $cobranzaCompra?->rut_cliente ?? '—',

                    'tipo' => $proveedor?->tipo ?? '—',
                    'detalle_documento' => $proveedor?->detalle_documento ?? 'Neto/Bruto',
                    'detalle_impuesto' => $proveedor?->detalle_impuesto ?? 'Impuesto',
                    'final' => $proveedor?->final ?? 'Final',

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





    public function show(SuscripcionLiquidacionDetalle $detalle, SuscripcionLiquidacionResumenService $resumenService) 
    {
        $detalle->load([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
        ]);

        $suscripcionProveedorId = $detalle->asignacion?->suscripcion_proveedor_id;

        if (!$suscripcionProveedorId) {
            abort(404, 'No se encontró el proveedor de suscripción asociado.');
        }

        $detallesProveedor = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
            'asignacion.opvPuntos',
        ])
        ->whereHas('asignacion', function ($query) use ($suscripcionProveedorId) {
            $query->where('suscripcion_proveedor_id', $suscripcionProveedorId);
        })
        ->where('anio', $detalle->anio)
        ->where('mes', $detalle->mes)
        ->orderBy('codigo')
        ->get();

        $calculosDetalle = $resumenService->calcularPorDetalles($detallesProveedor);

        $proveedor = $detalle->asignacion?->suscripcionProveedor;
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

        $detallesAnioProveedor = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
        ])
        ->whereHas('asignacion', function ($query) use ($suscripcionProveedorId) {
            $query->where('suscripcion_proveedor_id', $suscripcionProveedorId);
        })
        ->where('anio', $detalle->anio)
        ->orderBy('mes')
        ->orderBy('codigo')
        ->get();

        $prefacturasProveedorAnio = $detallesAnioProveedor
            ->groupBy('mes')
            ->map(function ($items) use ($resumenService, $meses) {
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
            'opvPendientes'
        ));
    }




    public function pdf(SuscripcionLiquidacionDetalle $detalle, SuscripcionLiquidacionResumenService $resumenService) 
    {
        $detalle->load([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
        ]);

        $suscripcionProveedorId = $detalle->asignacion?->suscripcion_proveedor_id;

        if (!$suscripcionProveedorId) {
            abort(404, 'No se encontró el proveedor de suscripción asociado.');
        }

        $detallesProveedor = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
        ])
        ->whereHas('asignacion', function ($query) use ($suscripcionProveedorId) {
            $query->where('suscripcion_proveedor_id', $suscripcionProveedorId);
        })
        ->where('anio', $detalle->anio)
        ->where('mes', $detalle->mes)
        ->orderBy('codigo')
        ->get();

        $calculosDetalle = $resumenService->calcularPorDetalles($detallesProveedor);

        $proveedor = $detalle->asignacion?->suscripcionProveedor;
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

        $nombreProveedor = $cobranzaCompra?->razon_social ?? 'Proveedor';
        $tipo = $proveedor?->tipo ?? 'DOC';

        $nombreArchivoProveedor = str_replace(
            ' ',
            '_',
            preg_replace('/[^A-Za-z0-9\s]/', '', $nombreProveedor)
        );

        $nombreArchivo = "PreFactura_Susc_{$tipo}_{$nombreArchivoProveedor}_{$detalle->anio}_{$detalle->mes}.pdf";

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
        ])->setPaper('letter', 'portrait');

        return $pdf->stream($nombreArchivo);
    }




    public function pdfMasivo(Request $request, SuscripcionPrefacturaZipService $zipService)
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

        $query = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
        ])
        ->where('anio', $anio)
        ->where('mes', $mes);

        if ($proveedorFiltro !== '') {
            $query->whereHas('asignacion.suscripcionProveedor.cobranzaCompra', function ($q) use ($proveedorFiltro) {
                $q->where('razon_social', 'like', '%' . $proveedorFiltro . '%');
            });
        }

        if ($rutFiltro !== '') {
            $query->whereHas('asignacion.suscripcionProveedor.cobranzaCompra', function ($q) use ($rutFiltro) {
                $q->where('rut_cliente', 'like', '%' . $rutFiltro . '%');
            });
        }

        if ($tipoFiltro !== '') {
            $query->whereHas('asignacion.suscripcionProveedor', function ($q) use ($tipoFiltro) {
                $q->where('tipo', $tipoFiltro);
            });
        }

        $detallesBase = $query
            ->orderBy('codigo')
            ->get();

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
            ->download($resultado['zip_path'], $resultado['zip_file_name'])
            ->deleteFileAfterSend(true);
    }



    public function generarMes(Request $request)
    {
        $request->validate([
            'anio_generar' => 'required|integer|min:2020|max:2100',
            'mes_generar' => 'required|integer|min:1|max:12',
        ]);

        $anio = (int) $request->anio_generar;
        $mes = (int) $request->mes_generar;
        $proveedorActual = trim((string) $request->input('proveedor_actual'));

        $asignaciones = Asignaciones::with([
            'transportista',
            'suscripcionProveedor.cobranzaCompra',
            'opvPuntos',
        ])
        ->orderBy('codigo')
        ->get();

        $creados = 0;
        $duplicados = 0;
        $opvSinRutas = collect();

        foreach ($asignaciones as $asignacion) {
            $existe = SuscripcionLiquidacionDetalle::where('suscripcion_asignacion_id', $asignacion->id)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

            if ($existe) {
                $duplicados++;
                continue;
            }

            if ($this->esAsignacionOPV($asignacion) && $asignacion->opvPuntos->count() === 0) {
                $nombreResponsable = $asignacion->transportista?->nombre_transportista
                    ?? $asignacion->suscripcionProveedor?->cobranzaCompra?->razon_social
                    ?? 'Sin transportista';

                $punto = $asignacion->punto_1 ?? 'Sin punto';

                $opvSinRutas->push($nombreResponsable . ' / ' . $punto);

                continue;
            }

            $calculo = $this->calcularDetalleMensual(
                $asignacion,
                $anio,
                $mes,
                0
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

            $creados++;
        }

        $params = [
            'anio' => $anio,
            'mes' => $mes,
        ];

        if ($proveedorActual !== '') {
            $params['proveedor'] = $proveedorActual;
        }

        $mensaje = "Mes generado correctamente. Creados: {$creados}.";

        if ($duplicados > 0) {
            $mensaje .= " Registros ya existentes no duplicados: {$duplicados}.";
        }

        if ($opvSinRutas->isNotEmpty()) {
            $mensaje .= ' No se generaron las siguientes rutas OPV porque no tienen locales OPV asignados: ';
            $mensaje .= $opvSinRutas->unique()->implode('; ') . '.';
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