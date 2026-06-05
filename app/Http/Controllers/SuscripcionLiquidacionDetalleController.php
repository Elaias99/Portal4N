<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionLiquidacionDetalle;
use App\Services\Calendar\ChileCalendarService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Suscripciones\SuscripcionLiquidacionResumenService;
use Illuminate\Http\Request;
use ZipArchive;

class SuscripcionLiquidacionDetalleController extends Controller
{




    public function index(Request $request, SuscripcionLiquidacionResumenService $resumenService)
    {
        $proveedor = trim((string) $request->input('proveedor'));
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

        $query = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
        ]);

        if ($proveedor !== '') {
            $query->whereHas('asignacion.suscripcionProveedor.cobranzaCompra', function ($q) use ($proveedor) {
                $q->where('razon_social', 'like', '%' . $proveedor . '%');
            });
        }

        if ($anio) {
            $query->where('anio', $anio);
        }

        if ($mes) {
            $query->where('mes', $mes);
        }

        $totalPeriodo = (clone $query)->sum('total');
        $cantidadRegistros = (clone $query)->count();

        $detalles = $query
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('codigo')
            ->paginate(10)
            ->appends($request->query());

        $calculosDetalle = $resumenService->calcularPorDetalles(
            $detalles->getCollection()
        );

        return view('suscripciones.liquidacion_detalles.index', compact(
            'detalles',
            'proveedor',
            'anio',
            'mes',
            'meses',
            'totalPeriodo',
            'cantidadRegistros',
            'calculosDetalle'
        ));
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





    public function show( SuscripcionLiquidacionDetalle $detalle, SuscripcionLiquidacionResumenService $resumenService) 
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

        return view('suscripciones.liquidacion_detalles.show', compact(
            'detalle',
            'detallesProveedor',
            'calculosDetalle',
            'proveedor',
            'cobranzaCompra',
            'totalBruto',
            'totalImpuesto',
            'totalLiquido',
            'meses'
        ));
    }




    public function pdf( SuscripcionLiquidacionDetalle $detalle, SuscripcionLiquidacionResumenService $resumenService) 
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




    public function pdfMasivo(Request $request, SuscripcionLiquidacionResumenService $resumenService)
    {
        $request->validate([
            'anio_pdf' => 'required|integer|min:2020|max:2100',
            'mes_pdf' => 'required|integer|min:1|max:12',
            'proveedor_pdf' => 'nullable|string',
        ]);

        $anio = (int) $request->anio_pdf;
        $mes = (int) $request->mes_pdf;
        $proveedorFiltro = trim((string) $request->proveedor_pdf);

        $query = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.transportista',
        ])
        ->where('anio', $anio)
        ->where('mes', $mes);

        if ($proveedorFiltro !== '') {
            $query->whereHas('asignacion.suscripcionProveedor.cobranzaCompra', function ($q) use ($proveedorFiltro) {
                $q->where('razon_social', 'like', '%' . $proveedorFiltro . '%');
            });
        }

        $detallesBase = $query->get();

        if ($detallesBase->isEmpty()) {
            return back()->withErrors([
                'pdf_masivo' => 'No existen detalles para generar PDFs con los filtros seleccionados.',
            ]);
        }

        $proveedorIds = $detallesBase
            ->map(fn ($detalle) => $detalle->asignacion?->suscripcion_proveedor_id)
            ->filter()
            ->unique()
            ->values();

        $tmpDir = storage_path('app/temp_prefacturas');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $zipFileName = 'prefacturas_suscripciones_' . $anio . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '_' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors([
                'pdf_masivo' => 'No se pudo crear el archivo ZIP.',
            ]);
        }

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

        $generados = 0;

        foreach ($proveedorIds as $suscripcionProveedorId) {
            $detallesProveedor = SuscripcionLiquidacionDetalle::with([
                'asignacion.suscripcionProveedor.cobranzaCompra',
                'asignacion.transportista',
            ])
            ->whereHas('asignacion', function ($query) use ($suscripcionProveedorId) {
                $query->where('suscripcion_proveedor_id', $suscripcionProveedorId);
            })
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->orderBy('codigo')
            ->get();

            if ($detallesProveedor->isEmpty()) {
                continue;
            }

            $detalle = $detallesProveedor->first();

            $calculosDetalle = $resumenService->calcularPorDetalles($detallesProveedor);

            $proveedor = $detalle->asignacion?->suscripcionProveedor;
            $cobranzaCompra = $proveedor?->cobranzaCompra;

            $totalBruto = $detallesProveedor->sum('total');
            $totalImpuesto = $calculosDetalle->sum('total_impuesto');
            $totalLiquido = $calculosDetalle->sum('liquido');

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

            $nombreProveedor = $cobranzaCompra?->razon_social ?? 'PROVEEDOR';
            $tipo = $proveedor?->tipo ?? 'DOC';

            $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreProveedor);
            $nombreLimpio = preg_replace('/_+/', '_', $nombreLimpio);
            $nombreLimpio = trim($nombreLimpio, '_');

            $pdfFileName = 'Prefactura_Susc_' . $tipo . '_' . $nombreLimpio . '_' . $anio . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf';

            $zip->addFromString($pdfFileName, $pdf->output());

            $generados++;
        }

        $zip->close();

        if ($generados === 0) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }

            return back()->withErrors([
                'pdf_masivo' => 'No se generó ningún PDF.',
            ]);
        }

        return response()
            ->download($zipPath, $zipFileName)
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

        $asignaciones = Asignaciones::orderBy('codigo')->get();

        $creados = 0;
        $omitidos = 0;

        foreach ($asignaciones as $asignacion) {
            $existe = SuscripcionLiquidacionDetalle::where('suscripcion_asignacion_id', $asignacion->id)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

            if ($existe) {
                $omitidos++;
                continue;
            }

            if ($this->esAsignacionOPV($asignacion) && $asignacion->opvPuntos()->count() === 0) {
                $omitidos++;
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

        return redirect()
            ->route('suscripciones.liquidacion-detalles.index', $params)
            ->with('success', "Mes generado correctamente. Creados: {$creados}. Omitidos por duplicados: {$omitidos}.");
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