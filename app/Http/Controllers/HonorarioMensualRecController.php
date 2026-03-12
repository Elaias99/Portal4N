<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HonorarioMensualRec;
use App\Services\Sii\HonorarioMensualRecParser;
use App\Models\HonorarioMensualRecTotal;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;
use App\Models\MovimientoHonorarioMensualRec;
use Illuminate\Support\Facades\Log;
use App\Models\CobranzaCompra;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Abono;
use App\Models\Cruce;
use App\Models\Pago;
use App\Models\ProntoPago;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\HonorariosPagoMasivoExport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ZipArchive;
use App\Exports\ExportHonorarioMensual;
use App\Models\HonorarioPagoProgramado;



class HonorarioMensualRecController extends Controller
{
    

    // =========================
    // PANEL DE ACCESO A LOS DOS MÓDULOS DE BOLETA
    // =========================
    public function panel(Request $request)
    {
        $usuariosFinanzas = [1, 405];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        $hoy = Carbon::today();

        $programadosHoy = HonorarioPagoProgramado::with([
            'honorarioMensualRec.empresa:id,Nombre',
            'honorarioMensualRec.cobranzaCompra:id,servicio,razon_social',
        ])
        ->whereDate('fecha_programada', $hoy)
        ->whereHas('honorarioMensualRec', function ($q) {
            $q->where('saldo_pendiente', '>', 0)
            ->doesntHave('pagos')
            ->doesntHave('prontoPagos');
        })
        ->orderBy('fecha_programada')
        ->get();

        $programadosAtrasados = HonorarioPagoProgramado::with([
            'honorarioMensualRec.empresa:id,Nombre',
            'honorarioMensualRec.cobranzaCompra:id,servicio,razon_social',
        ])
        ->whereDate('fecha_programada', '<', $hoy)
        ->whereHas('honorarioMensualRec', function ($q) {
            $q->where('saldo_pendiente', '>', 0)
            ->doesntHave('pagos')
            ->doesntHave('prontoPagos');
        })
        ->orderBy('fecha_programada')
        ->get();

        return view('boleta_mensual.panel_acceso.panel', compact(
            'programadosHoy',
            'programadosAtrasados'
        ));
    }



    public function index(Request $request)
    {
        $query = HonorarioMensualRec::with([
            'empresa:id,Nombre',
            'cobranzaCompra:id,servicio,razon_social',

            'abonos:id,honorario_mensual_rec_id,fecha_abono',
            'cruces:id,honorario_mensual_rec_id,fecha_cruce',
            'pagos:id,honorario_mensual_rec_id,fecha_pago',
            'prontoPagos:id,honorario_mensual_rec_id,fecha_pronto_pago',

            'pagoProgramado:id,honorario_mensual_rec_id,fecha_programada',
        ]);


        // =========================
        // FILTRO: EMPRESA
        // =========================
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        // =========================
        // FILTRO: AÑO
        // =========================
        if ($request->filled('anio')) {
            $query->where('anio', $request->anio);
        }

        // =========================
        // FILTRO: MES
        // =========================
        if ($request->filled('mes')) {
            $query->where('mes', $request->mes);
        }

        // =========================
        // FILTRO: RAZÓN SOCIAL EMISOR
        // =========================
        if ($request->filled('razon_social_emisor')) {
            $query->where(
                'razon_social_emisor',
                'like',
                '%' . $request->razon_social_emisor . '%'
            );
        }

        // =========================
        // FILTRO: RUT EMISOR
        // =========================
        if ($request->filled('rut_emisor')) {
            $query->where(
                'rut_emisor',
                'like',
                '%' . $request->rut_emisor . '%'
            );
        }

        // =========================
        // FILTRO: FOLIO
        // =========================
        if ($request->filled('folio')) {
            $query->where('folio', $request->folio);
            //exacto; cambia a like si lo quieres parcial
        }



        // =========================
        // FILTRO: FECHA DOCUMENTO (fecha_emision)
        // =========================
        if ($request->filled('fecha_emision_desde')) {
            $query->whereDate(
                'fecha_emision',
                '>=',
                $request->fecha_emision_desde
            );
        }

        if ($request->filled('fecha_emision_hasta')) {
            $query->whereDate(
                'fecha_emision',
                '<=',
                $request->fecha_emision_hasta
            );
        }

        // =========================
        // FILTRO: FECHA VENCIMIENTO (fecha_vencimiento)
        // =========================
        if ($request->filled('fecha_vencimiento_desde')) {
            $query->whereDate(
                'fecha_vencimiento',
                '>=',
                $request->fecha_vencimiento_desde
            );
        }

        if ($request->filled('fecha_vencimiento_hasta')) {
            $query->whereDate(
                'fecha_vencimiento',
                '<=',
                $request->fecha_vencimiento_hasta
            );
        }


        // =========================
        // FILTRO: SALDO
        // =========================

        if ($request->filled('saldo_monto')) {

            // Normalizar número (quita puntos/ comas/ espacios)
            $monto = (float) str_replace(['.', ',', ' '], '', (string) $request->saldo_monto);

            // Determinar tipo (default: pendiente)
            $tipo = $request->input('saldo_tipo', 'pendiente');

            // Mapear tipo -> columna real (whitelist)
            $map = [
                'pendiente' => 'saldo_pendiente',
                'original'  => 'monto_pagado', // si “original” debe ser monto_bruto, cámbialo aquí
            ];

            // Fallback seguro
            $columna = $map[$tipo] ?? 'saldo_pendiente';

            // Aplicar filtro con tolerancia ±1
            $query->whereBetween($columna, [$monto - 1, $monto + 1]);
        }


        // =========================
        // FILTRO: SERVICIO (EXPLÍCITO)
        // =========================
        if ($request->filled('servicio_tipo') && $request->filled('servicio_valor')) {

            $tipo   = $request->servicio_tipo;
            $valor  = $request->servicio_valor;

            if ($tipo === 'manual') {

                // Buscar SOLO en servicio_manual
                $query->where('servicio_manual', 'like', '%' . $valor . '%');

            }

            if ($tipo === 'proveedor') {

                // Buscar SOLO en cobranzaCompra->servicio
                $query->whereHas('cobranzaCompra', function ($q) use ($valor) {
                    $q->where('servicio', 'like', '%' . $valor . '%');
                });

            }
        }

        // =========================
        // ORDEN + PAGINACIÓN
        // =========================
        $registros = $query
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('fecha_emision', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // =========================
        // DATOS PARA SELECTORES
        // =========================
        $empresas = \App\Models\Empresa::orderBy('Nombre')->get();

        $anios = HonorarioMensualRec::select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return view('boleta_mensual.index', compact(
            'registros',
            'empresas',
            'anios'
        ));
    }




    public function show(HonorarioMensualRec $honorario)
    {
        // Cargar relaciones financieras necesarias para el detalle
        $honorario->load([
            'empresa',
            'cobranzaCompra',
            'abonos',
            'cruces',
            'pagos.user',
            'prontoPagos.user',
        ]);

        return view('boleta_mensual.show', compact('honorario'));
    }






    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file',
        ]);

        $parser = new HonorarioMensualRecParser(
            $request->file('archivo')
        );

        $preview = $parser->parse();

        // =========================
        // META DESDE SII
        // =========================
        // =========================
        // NORMALIZAR RUT CONTRIBUYENTE
        // =========================
        $rutArchivo = $preview['meta']['rut_contribuyente'];

        // quitar todo excepto números y K
        $rutLimpio = strtoupper(
            preg_replace('/[^0-9K]/', '', $rutArchivo)
        );

        $cuerpo = substr($rutLimpio, 0, -1);
        $dv     = substr($rutLimpio, -1);

        $rutFormateado = number_format($cuerpo, 0, '', '.') . '-' . $dv;

        // =========================
        // BUSCAR EMPRESA
        // =========================
        $empresa = Empresa::where('rut', $rutFormateado)->first();
        if (!$empresa) {

            abort(422, 'Empresa no encontrada para el RUT informado por el SII.');
        }


        // =========================
        // ADJUNTAR EMPRESA A PREVIEW
        // =========================
        $preview['empresa'] = [
            'id'     => $empresa->id,
            'nombre' => $empresa->Nombre,
            'rut'    => $empresa->rut,
        ];


        // =========================
        // DETECTAR PROVEEDORES FALTANTES
        // =========================

        // 1. Obtener RUTs únicos desde el archivo
        $rutsEmisores = collect($preview['registros'])
            ->pluck('rut_emisor')
            ->filter()
            ->unique()
            ->values();

        // 2. Buscar cuáles existen en cobranza_compras
        $rutsExistentes = \App\Models\CobranzaCompra::whereIn('rut_cliente', $rutsEmisores)
            ->pluck('rut_cliente')
            ->toArray();

        // 3. Determinar faltantes
        $proveedoresFaltantes = $rutsEmisores
            ->reject(fn($rut) => in_array($rut, $rutsExistentes))
            ->values();

        // 4. Armar estructura detallada para la vista
        $preview['proveedores_faltantes'] = $proveedoresFaltantes
            ->map(function ($rut) use ($preview) {
                $registro = collect($preview['registros'])
                    ->firstWhere('rut_emisor', $rut);

                return [
                    'rut_emisor' => $rut,
                    'razon_social_emisor' => $registro['razon_social_emisor'] ?? null,
                ];
            })
            ->all();

        // =========================
        // CALCULAR TOTALES
        // =========================
        $totales = [
            'bruto'    => 0,
            'retenido' => 0,
            'pagado'   => 0,
        ];

        foreach ($preview['registros'] as $fila) {
            if (($fila['estado'] ?? null) !== 'ANULADA') {
                $totales['bruto']    += (int) ($fila['monto_bruto'] ?? 0);
                $totales['retenido'] += (int) ($fila['monto_retenido'] ?? 0);
                $totales['pagado']   += (int) ($fila['monto_pagado'] ?? 0);
            }
        }

        $preview['totales'] = $totales;


        return redirect()
            ->route('honorarios.mensual.index')
            ->with('preview', $preview)
            ->with('info', 'Archivo analizado correctamente. Revisa la previsualización.');
    }

    public function store(Request $request)
    {
        $data = json_decode(
            base64_decode($request->input('data')),
            true
        );

        $meta      = $data['meta'];
        $registros = $data['registros'];
        $empresaId = $data['empresa']['id'];

        // =========================
        // GUARDAR DETALLE (ÚNICA RELACIÓN CON EMPRESA)
        // =========================

        foreach ($registros as $r) {

            $rutEmisor = $r['rut_emisor'] ?? null;

            // Buscar proveedor en cobranza_compras
            $cobranza = $rutEmisor
                ? CobranzaCompra::where('rut_cliente', $rutEmisor)->first()
                : null;

            if (!$cobranza) {
                Log::warning('HONORARIO SIN PROVEEDOR DETECTADO', [
                    'empresa_id' => $empresaId,
                    'rut_emisor' => $rutEmisor,
                    'folio' => $r['folio'] ?? null,
                    'anio' => $meta['anio'] ?? null,
                    'mes' => $meta['mes'] ?? null,
                ]);
            }


            // =========================
            // ESTADO FINANCIERO INICIAL + FECHA VENCIMIENTO
            // =========================
            $estadoFinancieroInicial = null;
            $fechaVencimiento = null;

            if ($cobranza && $cobranza->creditos !== null) {

                $fechaEmision = $r['fecha_emision']
                    ? Carbon::parse($r['fecha_emision'])
                    : null;

                if ($fechaEmision) {

                    $fechaVencimiento = $fechaEmision
                        ->copy()
                        ->addDays((int) $cobranza->creditos);

                    $estadoFinancieroInicial = $fechaVencimiento->isPast()
                        ? 'Vencido'
                        : 'Al día';
                }
            }


                    $honorario = HonorarioMensualRec::where([
                        'empresa_id'        => $empresaId,
                        'rut_contribuyente' => $meta['rut_contribuyente'],
                        'anio'              => $meta['anio'],
                        'mes'               => $meta['mes'],
                        'rut_emisor'        => $rutEmisor,
                        'folio'             => $r['folio'],
                    ])->first();

                    if ($honorario) {


                    Log::info('ACTUALIZANDO HONORARIO EXISTENTE', [
                        'folio' => $r['folio'],
                        'rut_emisor' => $rutEmisor,
                        'cobranza_compra_id' => $cobranza?->id,
                    ]);







                        //  EXISTE → solo actualizar datos SII
                        $honorario->update([
                            'razon_social'         => $meta['razon_social'],
                            'fecha_emision'        => $r['fecha_emision'],
                            'estado'               => $r['estado'],
                            'fecha_anulacion'      => $r['fecha_anulacion'],
                            'razon_social_emisor'  => $r['razon_social_emisor'],
                            'sociedad_profesional' => $r['sociedad_profesional'],

                            'monto_bruto'    => $r['monto_bruto'],
                            'monto_retenido' => $r['monto_retenido'],
                            'monto_pagado'   => $r['monto_pagado'],

                            'cobranza_compra_id' => $cobranza?->id,

                            //  NO tocar:
                            // saldo_pendiente
                            // estado_financiero
                            // servicio_manual
                        ]);

                    } else {

                        Log::info('CREANDO HONORARIO', [
                            'folio' => $r['folio'],
                            'rut_emisor' => $rutEmisor,
                            'cobranza_compra_id' => $cobranza?->id,
                            'estado_financiero_inicial' => $estadoFinancieroInicial,
                            'fecha_vencimiento' => $fechaVencimiento,
                        ]);


                        //  NUEVO → inicializar capa financiera
                        HonorarioMensualRec::create([
                            'empresa_id'        => $empresaId,
                            'rut_contribuyente' => $meta['rut_contribuyente'],
                            'anio'              => $meta['anio'],
                            'mes'               => $meta['mes'],
                            'rut_emisor'        => $rutEmisor,
                            'folio'             => $r['folio'],

                            'razon_social'         => $meta['razon_social'],
                            'fecha_emision'        => $r['fecha_emision'],
                            'estado'               => $r['estado'],
                            'fecha_anulacion'      => $r['fecha_anulacion'],
                            'razon_social_emisor'  => $r['razon_social_emisor'],
                            'sociedad_profesional' => $r['sociedad_profesional'],

                            'monto_bruto'    => $r['monto_bruto'],
                            'monto_retenido' => $r['monto_retenido'],
                            'monto_pagado'   => $r['monto_pagado'],

            
                            'saldo_pendiente'           => $r['monto_pagado'],
                            'estado_financiero_inicial' => $estadoFinancieroInicial,
                            'fecha_vencimiento'         => $fechaVencimiento,

                            'cobranza_compra_id' => $cobranza?->id,
                        ]);
                    }


        }



        // =========================
        // GUARDAR TOTALES (SIN empresa_id)
        // =========================
        if (!empty($data['totales'])) {

            HonorarioMensualRecTotal::updateOrCreate(
                [
                    'rut_contribuyente' => $meta['rut_contribuyente'],
                    'anio'              => $meta['anio'],
                    'mes'               => $meta['mes'],
                ],
                [
                    'razon_social'   => $meta['razon_social'],
                    'monto_bruto'    => $data['totales']['bruto'],
                    'monto_retenido' => $data['totales']['retenido'],
                    'monto_pagado'   => $data['totales']['pagado'],
                ]
            );
        }

        return redirect()
            ->route('honorarios.mensual.index')
            ->with('success', 'Honorarios mensuales guardados correctamente.');
    }



    // GUARDADO DE LOS ESTADOS MANUALES

    public function storeAbono(Request $request, HonorarioMensualRec $honorario)
    {
        $request->validate([
            'monto_abono' => 'required|integer|min:1',
            'fecha_abono' => 'required|date|before_or_equal:today',
        ]);

        $montoAbono = (int) $request->monto_abono;

        //  Snapshot previo
        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        if ($montoAbono > $saldoAnterior) {
            return back()->withErrors([
                'monto_abono' => 'El abono no puede ser mayor al saldo pendiente actual.'
            ]);
        }

        //  Registrar abono
        $honorario->abonos()->create([
            'monto'       => $montoAbono,
            'fecha_abono' => $request->fecha_abono,
        ]);

        //  Recalcular saldo
        $honorario->recalcularSaldoPendiente();

        // Actualizar estado
        $honorario->update([
            'estado_financiero'       => 'Abono',
            'fecha_estado_financiero' => now(),
        ]);

        $honorario->refresh();

        // Registrar movimiento
        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => 'Abono',
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Registro de abono',
            'descripcion'     => "Se registró un abono de {$montoAbono}.",
            'datos_anteriores'=> [
                'saldo' => $saldoAnterior,
            ],
            'datos_nuevos'    => [
                'monto_abono' => $montoAbono,
                'saldo'       => $honorario->saldo_pendiente,
            ],
        ]);

        return back()->with('success', 'Abono registrado correctamente.');
    }



    public function storeCruce(Request $request, HonorarioMensualRec $honorario)
    {
        $request->validate([
            'monto_cruce' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
        ]);

        $montoCruce = (int) $request->monto_cruce;

        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        if ($montoCruce > $saldoAnterior) {
            return back()->withErrors([
                'monto_cruce' => 'El cruce no puede ser mayor al saldo pendiente actual.'
            ]);
        }

        $honorario->cruces()->create([
            'monto'              => $montoCruce,
            'fecha_cruce'        => $request->fecha_cruce,
            'cobranza_compra_id' => $request->cobranza_compra_id,
        ]);

        $honorario->recalcularSaldoPendiente();

        $honorario->update([
            'estado_financiero'       => 'Cruce',
            'fecha_estado_financiero' => now(),
        ]);

        $honorario->refresh();

        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => 'Cruce',
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Registro de cruce',
            'descripcion'     => "Se registró un cruce de {$montoCruce}.",
            'datos_anteriores'=> [
                'saldo' => $saldoAnterior,
            ],
            'datos_nuevos'    => [
                'monto_cruce' => $montoCruce,
                'saldo'       => $honorario->saldo_pendiente,
            ],
        ]);

        return back()->with('success', 'Cruce registrado correctamente.');
    }


    public function storePago(Request $request, HonorarioMensualRec $honorario)
    {
        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
        ]);

        if ($honorario->pagos()->exists()) {
            return back()->withErrors([
                'fecha_pago' => 'Este honorario ya tiene un pago registrado.'
            ]);
        }

        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        $honorario->pagos()->create([
            'fecha_pago' => $request->fecha_pago,
            'user_id'    => Auth::id(),
        ]);

        $honorario->update([
            'estado_financiero'       => 'Pago',
            'fecha_estado_financiero' => now(),
            'saldo_pendiente'         => 0,
        ]);

        $honorario->refresh();

        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => 'Pago',
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Registro de pago',
            'descripcion'     => "Se registró el pago total del honorario.",
            'datos_anteriores'=> [
                'saldo' => $saldoAnterior,
            ],
            'datos_nuevos'    => [
                'saldo' => 0,
            ],
        ]);

        return back()->with('success', 'Pago registrado correctamente.');
    }


    public function storePagoMasivo(Request $request)
    {
        $request->validate([
            'honorarios'   => 'required|array|min:1',
            'honorarios.*' => 'integer|exists:honorarios_mensuales_rec,id',
            'fecha_pago'   => 'required|date|before_or_equal:today',
        ]);

        DB::transaction(function () use ($request) {

            foreach ($request->honorarios as $honorarioId) {

                $honorario = HonorarioMensualRec::find($honorarioId);

                if (!$honorario) {
                    continue;
                }

                // NO elegible para pago masivo
                if (
                    $honorario->pagos()->exists() ||
                    $honorario->prontoPagos()->exists() ||
                    $honorario->saldo_pendiente <= 0
                ) {
                    continue;
                }

                // Snapshot previo
                $estadoAnterior = $honorario->estado_financiero_final;
                $saldoAnterior  = $honorario->saldo_pendiente;

                // =========================
                // CREAR PAGO
                // =========================
                $honorario->pagos()->create([
                    'fecha_pago' => $request->fecha_pago,
                    'user_id'    => Auth::id(),
                ]);

                // =========================
                // ACTUALIZAR HONORARIO
                // =========================
                $honorario->update([
                    'estado_financiero'       => 'Pago',
                    'fecha_estado_financiero' => now(),
                    'saldo_pendiente'         => 0,
                ]);

                // =========================
                // REGISTRAR MOVIMIENTO
                // =========================
                $honorario->movimientos()->create([
                    'usuario_id'      => Auth::id(),
                    'estado_anterior' => $estadoAnterior,
                    'nuevo_estado'    => 'Pago',
                    'fecha_cambio'    => now(),
                    'tipo_movimiento' => 'Pago masivo',
                    'descripcion'     => 'Pago registrado mediante operación masiva.',
                    'datos_anteriores'=> [
                        'saldo' => $saldoAnterior,
                    ],
                    'datos_nuevos'    => [
                        'saldo' => 0,
                    ],
                ]);
            }
        });

        return redirect()
            ->route('honorarios.mensual.index')
            ->with('success', 'Pago masivo registrado correctamente.');
    }






    public function storePagoMasivoExport(Request $request)
    {
        $request->validate([
            'honorarios'   => 'required|array|min:1',
            'honorarios.*' => 'integer|exists:honorarios_mensuales_rec,id',
            'fecha_pago'   => 'required|date|before_or_equal:today',
        ]);



        /**
         * [
         *   empresa_id => Collection<HonorarioMensualRec>
         * ]
         */
        $honorariosPorEmpresa = collect();

        // =========================
        // TRANSACCIÓN: PAGOS + AGRUPACIÓN
        // =========================
        DB::transaction(function () use ($request, &$honorariosPorEmpresa) {

            foreach ($request->honorarios as $honorarioId) {

                $honorario = HonorarioMensualRec::with('empresa')->find($honorarioId);


                if (!$honorario) {

                
                    continue;
                }

                // No elegible
                if (
                    $honorario->pagos()->exists() ||
                    $honorario->prontoPagos()->exists() ||
                    $honorario->saldo_pendiente <= 0
                ) {
                    continue;
                }

                $estadoAnterior = $honorario->estado_financiero_final;
                $saldoAnterior  = $honorario->saldo_pendiente;

                // =========================
                // CREAR PAGO
                // =========================
                $honorario->pagos()->create([
                    'fecha_pago' => $request->fecha_pago,
                    'user_id'    => Auth::id(),
                ]);

                // =========================
                // ACTUALIZAR HONORARIO
                // =========================
                $honorario->update([
                    'estado_financiero'       => 'Pago',
                    'fecha_estado_financiero' => now(),
                    'saldo_pendiente'         => 0,
                ]);

                // =========================
                // REGISTRAR MOVIMIENTO
                // =========================
                $honorario->movimientos()->create([
                    'usuario_id'       => Auth::id(),
                    'estado_anterior'  => $estadoAnterior,
                    'nuevo_estado'     => 'Pago',
                    'fecha_cambio'     => now(),
                    'tipo_movimiento'  => 'Pago masivo con exportación',
                    'descripcion'      => 'Pago registrado mediante operación masiva con exportación.',
                    'datos_anteriores' => [
                        'saldo' => $saldoAnterior,
                    ],
                    'datos_nuevos'     => [
                        'saldo' => 0,
                    ],
                ]);

                // =========================
                // AGRUPAR POR EMPRESA
                // =========================
                $empresaId = $honorario->empresa_id;

                if (!$honorariosPorEmpresa->has($empresaId)) {
                    $honorariosPorEmpresa[$empresaId] = collect();
                }

                $honorariosPorEmpresa[$empresaId]->push(
                    $honorario->fresh()
                );
            }
        });

        Log::info('HM agrupación resultante', [
            'empresas_distintas' => $honorariosPorEmpresa->count(),
            'empresa_ids'        => $honorariosPorEmpresa->keys()->values()->all(),
            'por_empresa'        => $honorariosPorEmpresa->map(fn($c) => $c->count())->all(),
        ]);
        // =========================
        // SIN ZIP: generar tokens y devolver URLs de descarga
        // =========================
        $downloads = [];

        foreach ($honorariosPorEmpresa as $empresaId => $honorarios) {

            $token = (string) \Illuminate\Support\Str::uuid();

            // Guardar la Collection para que downloadPagoMasivoExcel($token) la consuma
            Cache::put("pago_masivo_excel:$token", $honorarios, now()->addMinutes(10));

            $downloads[] = [
                'url' => route('honorarios.mensual.pago.masivo.descargar', ['token' => $token]),
            ];
        }

        return response()->json([
            'ok' => true,
            'downloads' => $downloads,
        ]);
    }



    public function downloadPagoMasivoExcel(string $token)
    {
        $honorariosProcesados = Cache::get("pago_masivo_excel:$token");

        abort_if(!$honorariosProcesados || $honorariosProcesados->isEmpty(), 404, 'Exportación no disponible o expirada.');

        // One-shot (opcional)
        Cache::forget("pago_masivo_excel:$token");

        // =========================
        // OBTENER EMPRESA
        // =========================
        $honorario = $honorariosProcesados->first();
        $honorario->loadMissing('empresa');

        $nombreEmpresa = $honorario->empresa?->Nombre ?? 'Empresa';

        // Normalizar nombre empresa para archivo
        $nombreEmpresaArchivo = str_replace(
            ' ',
            '_',
            preg_replace('/[^A-Za-z0-9\s]/', '', $nombreEmpresa)
        );

        // =========================
        // NOMBRE ARCHIVO
        // =========================
        $fecha = now()->format('Y-m-d');

        $nombreArchivo = "{$nombreEmpresaArchivo}_honorarios_pago_masivo_{$fecha}.xlsx";

        return Excel::download(
            new HonorariosPagoMasivoExport($honorariosProcesados),
            $nombreArchivo
        );
    }






















    public function buscarHonorarios(Request $request)
    {
        $q = trim($request->get('q'));

        if ($q === '') {
            return response()->json([]);
        }

        $resultados = HonorarioMensualRec::query()
            ->where('saldo_pendiente', '>', 0)
            ->where(function ($q2) use ($q) {
                $q2->where('folio', 'like', "%{$q}%")
                ->orWhere('rut_emisor', 'like', "%{$q}%")
                ->orWhere('razon_social_emisor', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get([
                'id',
                'folio',
                'rut_emisor',
                'razon_social_emisor',
                'saldo_pendiente',
            ]);


        return response()->json($resultados);
    }







    public function storeProntoPago(Request $request, HonorarioMensualRec $honorario)
    {
        $request->validate([
            'fecha_pronto_pago' => 'required|date|before_or_equal:today',
        ]);

        if ($honorario->prontoPagos()->exists()) {
            return back()->withErrors([
                'fecha_pronto_pago' => 'Este honorario ya tiene un pronto pago registrado.'
            ]);
        }

        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        $honorario->prontoPagos()->create([
            'fecha_pronto_pago' => $request->fecha_pronto_pago,
            'user_id'           => Auth::id(),
        ]);

        $honorario->update([
            'estado_financiero'       => 'Pronto pago',
            'fecha_estado_financiero' => now(),
            'saldo_pendiente'         => 0,
        ]);

        $honorario->refresh();

        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => 'Pronto pago',
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Registro de pronto pago',
            'descripcion'     => "Se registró el pronto pago del honorario.",
            'datos_anteriores'=> [
                'saldo' => $saldoAnterior,
            ],
            'datos_nuevos'    => [
                'saldo' => 0,
            ],
        ]);

        return back()->with('success', 'Pronto pago registrado correctamente.');
    }



    public function storeEstado(Request $request)
    {

    
        $honorario = HonorarioMensualRec::findOrFail($request->honorario_id);

        switch ($request->estado_financiero) {

            case 'Abono':
                return $this->storeAbono($request, $honorario);

            case 'Cruce':
                return $this->storeCruce($request, $honorario);

            case 'Pago':
                return $this->storePago($request, $honorario);

            case 'Pronto pago':
                return $this->storeProntoPago($request, $honorario);
        }

        return back()->withErrors('Estado no válido');
    }


    public function historial()
    {
        $movimientos = MovimientoHonorarioMensualRec::with([
            'user',
            'honorario.empresa',
        ])
        ->orderBy('fecha_cambio', 'desc')
        ->paginate(30);

        return view('boleta_mensual.historial', compact('movimientos'));
    }


















    // Eliminación de estados manuales y reversión de cambios


    public function revertirAbono(int $abonoId)
    {
        
        // =========================
        // OBTENER ABONO (SIN BINDING)
        // =========================
        $abono = Abono::withoutGlobalScopes()->findOrFail($abonoId);

        // =========================
        // OBTENER HONORARIO ASOCIADO
        // =========================
        $honorario = $abono->honorarioMensualRec;

        if (!$honorario) {
            abort(404, 'Honorario asociado no encontrado.');
        }

        // =========================
        // SNAPSHOT PREVIO
        // =========================
        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        $montoAbono = $abono->monto;
        $fechaAbono = $abono->fecha_abono;

        // =========================
        // ELIMINAR ABONO
        // =========================
        $abono->delete();

        // =========================
        // RECALCULAR SALDO
        // =========================
        $honorario->recalcularSaldoPendiente();

        // =========================
        // RECALCULAR ESTADO FINANCIERO
        // =========================
        if ($honorario->pagos()->exists() || $honorario->prontoPagos()->exists()) {
            $nuevoEstado = 'Pago';

        } elseif ($honorario->abonos()->exists()) {
            $nuevoEstado = 'Abono';

        } elseif ($honorario->cruces()->exists()) {
            $nuevoEstado = 'Cruce';

        } else {
            // Estado automático según vencimiento
            $nuevoEstado = $honorario->fecha_vencimiento &&
                        $honorario->fecha_vencimiento->isPast()
                ? 'Vencido'
                : 'Al día';
        }

        $honorario->update([
            'estado_financiero'       => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
            'fecha_estado_financiero' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
        ]);


        $honorario->refresh();

        // =========================
        // REGISTRAR MOVIMIENTO
        // =========================
        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => $honorario->estado_financiero_final,
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Eliminación de abono',
            'descripcion'     => "Se eliminó un abono de {$montoAbono} registrado el {$fechaAbono}.",
            'datos_anteriores'=> [
                'saldo'       => $saldoAnterior,
                'monto_abono' => $montoAbono,
                'fecha_abono' => $fechaAbono,
            ],
            'datos_nuevos'    => [
                'saldo' => $honorario->saldo_pendiente,
            ],
        ]);

        return back()->with('success', 'Abono eliminado correctamente.');
    }

    public function revertirCruce(int $cruceId)
    {
        
        // =========================
        // OBTENER CRUCE (SIN BINDING)
        // =========================
        $cruce = Cruce::withoutGlobalScopes()->findOrFail($cruceId);

        // =========================
        // OBTENER HONORARIO ASOCIADO
        // =========================
        $honorario = $cruce->honorarioMensualRec;

        if (!$honorario) {
            abort(404, 'Honorario asociado no encontrado.');
        }

        // =========================
        // SNAPSHOT PREVIO
        // =========================
        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        $montoCruce = $cruce->monto;
        $fechaCruce = $cruce->fecha_cruce;

        // =========================
        // ELIMINAR CRUCE
        // =========================
        $cruce->delete();

        // =========================
        // RECALCULAR SALDO
        // =========================
        $honorario->recalcularSaldoPendiente();

        // =========================
        // RECALCULAR ESTADO FINANCIERO
        // =========================
        if ($honorario->pagos()->exists() || $honorario->prontoPagos()->exists()) {
            $nuevoEstado = 'Pago';

        } elseif ($honorario->abonos()->exists()) {
            $nuevoEstado = 'Abono';

        } elseif ($honorario->cruces()->exists()) {
            $nuevoEstado = 'Cruce';

        } else {
            // Estado automático según vencimiento
            $nuevoEstado = $honorario->fecha_vencimiento &&
                        $honorario->fecha_vencimiento->isPast()
                ? 'Vencido'
                : 'Al día';
        }

        $honorario->update([
            'estado_financiero'       => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
            'fecha_estado_financiero' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
        ]);



        $honorario->refresh();

        // =========================
        // REGISTRAR MOVIMIENTO
        // =========================
        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => $honorario->estado_financiero_final,
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Eliminación de cruce',
            'descripcion'     => "Se eliminó un cruce de {$montoCruce} registrado el {$fechaCruce}.",
            'datos_anteriores'=> [
                'saldo'       => $saldoAnterior,
                'monto_cruce' => $montoCruce,
                'fecha_cruce' => $fechaCruce,
            ],
            'datos_nuevos'    => [
                'saldo' => $honorario->saldo_pendiente,
            ],
        ]);

        return back()->with('success', 'Cruce eliminado correctamente.');
    }


    public function revertirPago(int $pagoId)
    {
        // =========================
        // OBTENER PAGO
        // =========================
        $pago = Pago::withoutGlobalScopes()->findOrFail($pagoId);

        // =========================
        // OBTENER HONORARIO
        // =========================
        $honorario = $pago->honorarioMensualRec;

        if (!$honorario) {
            abort(404, 'Honorario asociado no encontrado.');
        }

        // =========================
        // SNAPSHOT PREVIO
        // =========================
        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        $montoPago = $saldoAnterior;
        $fechaPago = $pago->fecha_pago;

        // =========================
        // ELIMINAR PAGO
        // =========================
        $pago->delete();

        // =========================
        // RECALCULAR SALDO
        // =========================
        $honorario->recalcularSaldoPendiente();

        // =========================
        // RECALCULAR ESTADO FINANCIERO
        // =========================
        if ($honorario->pagos()->exists() || $honorario->prontoPagos()->exists()) {
            // No debería pasar, pero se protege
            $nuevoEstado = 'Pago';

        } elseif ($honorario->abonos()->exists()) {
            $nuevoEstado = 'Abono';

        } elseif ($honorario->cruces()->exists()) {
            $nuevoEstado = 'Cruce';

        } else {
            // Volver a estado automático por fecha
            $nuevoEstado = $honorario->fecha_vencimiento &&
                        $honorario->fecha_vencimiento->isPast()
                ? 'Vencido'
                : 'Al día';
        }

        $honorario->update([
            'estado_financiero'       => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
            'fecha_estado_financiero' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
        ]);


        $honorario->refresh();

        // =========================
        // REGISTRAR MOVIMIENTO
        // =========================
        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => $honorario->estado_financiero_final,
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Eliminación de pago',
            'descripcion'     => "Se eliminó el pago total registrado el {$fechaPago}.",
            'datos_anteriores'=> [
                'saldo' => $saldoAnterior,
            ],
            'datos_nuevos'    => [
                'saldo' => $honorario->saldo_pendiente,
            ],
        ]);

        return back()->with('success', 'Pago eliminado correctamente.');
    }

    public function revertirProntoPago(int $prontoPagoId)
    {
        // =========================
        // OBTENER PRONTO PAGO
        // =========================
        $prontoPago = ProntoPago::withoutGlobalScopes()->findOrFail($prontoPagoId);

        // =========================
        // OBTENER HONORARIO
        // =========================
        $honorario = $prontoPago->honorarioMensualRec;

        if (!$honorario) {
            abort(404, 'Honorario asociado no encontrado.');
        }

        // =========================
        // SNAPSHOT PREVIO
        // =========================
        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        $montoProntoPago = $saldoAnterior;
        $fechaProntoPago = $prontoPago->fecha_pronto_pago;

        // =========================
        // ELIMINAR PRONTO PAGO
        // =========================
        $prontoPago->delete();

        // =========================
        // RECALCULAR SALDO
        // =========================
        $honorario->recalcularSaldoPendiente();

        // =========================
        // RECALCULAR ESTADO FINANCIERO
        // =========================
        if ($honorario->pagos()->exists() || $honorario->prontoPagos()->exists()) {
            // No debería ocurrir tras eliminar, pero se protege
            $nuevoEstado = 'Pago';

        } elseif ($honorario->abonos()->exists()) {
            $nuevoEstado = 'Abono';

        } elseif ($honorario->cruces()->exists()) {
            $nuevoEstado = 'Cruce';

        } else {
            // Volver a estado automático según fecha
            $nuevoEstado = $honorario->fecha_vencimiento &&
                        $honorario->fecha_vencimiento->isPast()
                ? 'Vencido'
                : 'Al día';
        }

        $honorario->update([
            'estado_financiero'       => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : $nuevoEstado,
            'fecha_estado_financiero' => in_array($nuevoEstado, ['Vencido', 'Al día']) ? null : now(),
        ]);


        $honorario->refresh();

        // =========================
        // REGISTRAR MOVIMIENTO
        // =========================
        $honorario->movimientos()->create([
            'usuario_id'      => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'nuevo_estado'    => $honorario->estado_financiero_final,
            'fecha_cambio'    => now(),
            'tipo_movimiento' => 'Eliminación de pronto pago',
            'descripcion'     => "Se eliminó el pronto pago registrado el {$fechaProntoPago}.",
            'datos_anteriores'=> [
                'saldo' => $saldoAnterior,
            ],
            'datos_nuevos'    => [
                'saldo' => $honorario->saldo_pendiente,
            ],
        ]);

        return back()->with('success', 'Pronto pago eliminado correctamente.');
    }



    public function updateServicio(Request $request, HonorarioMensualRec $honorario)
    {
        // Validación básica
        $request->validate([
            'servicio_manual' => 'required|string|max:255',
        ]);

        // Regla de negocio
        if (
            !$honorario->cobranzaCompra ||
            $honorario->cobranzaCompra->servicio !== 'Otro'
        ) {
            abort(403, 'Este honorario no permite definir servicio manual.');
        }

        $honorario->update([
            'servicio_manual' => $request->servicio_manual,
        ]);

        // (Luego aquí registraremos movimiento)

        return back()->with('success', 'Servicio definido correctamente.');
    }





    // Descargar EXCEL como exportación de honorarios mensuales


    public function export(Request $request)
    {
        return Excel::download(
            new ExportHonorarioMensual($request),
            'honorarios_mensuales_rec.xlsx'
        );
    }










    // Detectar proveedor nuevo
    public function storeProveedores(Request $request)
    {
        // =========================
        // VALIDACIÓN
        // =========================
        $request->validate([
            'proveedores' => 'required|array|min:1',
            'proveedores.*.rut_cliente' => 'required|string',
            'proveedores.*.razon_social' => 'required|string',
            'proveedores.*.servicio' => 'nullable|string',
            'proveedores.*.creditos' => 'nullable|integer|min:0',
            'proveedores.*.tipo' => 'nullable|string',
            'proveedores.*.facturacion' => 'nullable|string',
            'proveedores.*.forma_pago' => 'nullable|string',
            'proveedores.*.zona' => 'nullable|string',
            'proveedores.*.importancia' => 'nullable|string',
            'proveedores.*.responsable' => 'nullable|string',
            'proveedores.*.nombre_cuenta' => 'nullable|string',
            'proveedores.*.rut_cuenta' => 'nullable|string',
            'proveedores.*.numero_cuenta' => 'nullable|string',
            'proveedores.*.banco_id' => 'nullable|integer',
            'proveedores.*.tipo_cuenta_id' => 'nullable|integer',
        ]);

        $proveedores = $request->input('proveedores');

        DB::beginTransaction();

        try {

            foreach ($proveedores as $proveedor) {

                // Evitar duplicados si alguien lo crea en paralelo
                $existe = CobranzaCompra::where(
                    'rut_cliente',
                    $proveedor['rut_cliente']
                )->exists();

                if ($existe) {
                    continue;
                }

                CobranzaCompra::create([
                    'rut_cliente'     => $proveedor['rut_cliente'],
                    'razon_social'    => $proveedor['razon_social'],
                    'servicio'        => $proveedor['servicio'] ?? null,
                    'creditos'        => $proveedor['creditos'] ?? null,
                    'tipo'            => $proveedor['tipo'] ?? null,
                    'facturacion'     => $proveedor['facturacion'] ?? null,
                    'forma_pago'      => $proveedor['forma_pago'] ?? null,
                    'zona'            => $proveedor['zona'] ?? null,
                    'importancia'     => $proveedor['importancia'] ?? null,
                    'responsable'     => $proveedor['responsable'] ?? null,
                    'nombre_cuenta'   => $proveedor['nombre_cuenta'] ?? null,
                    'rut_cuenta'      => $proveedor['rut_cuenta'] ?? null,
                    'numero_cuenta'   => $proveedor['numero_cuenta'] ?? null,
                    'banco_id'        => $proveedor['banco_id'] ?? null,
                    'tipo_cuenta_id'  => $proveedor['tipo_cuenta_id'] ?? null,
                ]);
            }

            DB::commit();

            Log::info('PROVEEDORES CREADOS DESDE IMPORTACION HONORARIOS', [
                'cantidad' => count($proveedores),
                'usuario_id' => Auth::id(),
            ]);

            return redirect()
                ->route('honorarios.mensual.index')
                ->with('success', 'Proveedores creados correctamente. Puede continuar con la importación.');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('ERROR CREANDO PROVEEDORES DESDE IMPORTACION', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('honorarios.mensual.index')
                ->with('error', 'Ocurrió un error al crear los proveedores.');
        }
    }




    public function calendario(Request $request)
    {
        $query = HonorarioMensualRec::with('cobranzaCompra');

        // =========================
        // FILTRO: AÑO
        // =========================
        if ($request->filled('anio')) {
            $query->whereYear('fecha_emision', $request->anio);
        }

        // =========================
        // FILTRO: MES
        // =========================
        if ($request->filled('mes')) {
            $query->whereMonth('fecha_emision', $request->mes);
        }

        // =========================
        // FILTRO: SERVICIO
        // =========================
        if ($request->filled('servicio')) {
            $query->whereHas('cobranzaCompra', function ($q) use ($request) {
                $q->where('servicio', $request->servicio);
            });
        }

        $honorarios = $query
            ->orderBy('fecha_emision', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // Para los selectores
        $anios = HonorarioMensualRec::selectRaw('YEAR(fecha_emision) as anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        $servicios = [
            'COLABORADORES',
            'AGENCIAS',
            'COURIER',
            'SUSCRIPCIONES',
        ];

        return view('boleta_mensual.calendario', compact(
            'honorarios',
            'anios',
            'servicios'
        ));
    }




    public function storePagoProgramadoMasivo(Request $request)
    {
        $request->validate([
            'honorarios'   => 'required|array|min:1',
            'honorarios.*' => 'integer|exists:honorarios_mensuales_rec,id',
            'fecha_programada' => 'required|date|after_or_equal:today',
            'observacion'  => 'nullable|string|max:1000',
        ]);

        $programados = 0;
        $omitidos    = 0;

        DB::transaction(function () use ($request, &$programados, &$omitidos) {

            $ids = collect($request->honorarios)
                ->unique()
                ->values();

            foreach ($ids as $honorarioId) {

                $honorario = HonorarioMensualRec::with([
                    'pagos',
                    'prontoPagos',
                    'pagoProgramado',
                ])->find($honorarioId);

                if (!$honorario) {
                    $omitidos++;
                    continue;
                }

                // No programar si ya está cerrado o sin saldo
                if (
                    $honorario->pagos->isNotEmpty() ||
                    $honorario->prontoPagos->isNotEmpty() ||
                    (int) $honorario->saldo_pendiente <= 0
                ) {
                    $omitidos++;
                    continue;
                }

                HonorarioPagoProgramado::updateOrCreate(
                    
                    [
                        'honorario_mensual_rec_id' => $honorario->id,
                    ],
                    [
                        'fecha_programada' => $request->fecha_programada,
                        'user_id'          => Auth::id(),
                        'observacion'      => $request->observacion,
                    ]
                );

                $programados++;
            }
        });

        return back()->with(
            'success',
            "Próximo pago definido correctamente. Programados: {$programados}. Omitidos: {$omitidos}."
        );
    }



    public function storePagoProgramadoMasivoExport(Request $request)
    {
        $request->validate([
            'honorarios'        => 'required|array|min:1',
            'honorarios.*'      => 'integer|exists:honorarios_mensuales_rec,id',
            'fecha_programada'  => 'required|date|after_or_equal:today',
            'observacion'       => 'nullable|string|max:1000',
        ]);

        $honorariosPorEmpresa = collect();

        DB::transaction(function () use ($request, &$honorariosPorEmpresa) {

            $ids = collect($request->honorarios)
                ->unique()
                ->values();

            foreach ($ids as $honorarioId) {

                $honorario = HonorarioMensualRec::with([
                    'empresa',
                    'pagos',
                    'prontoPagos',
                    'pagoProgramado',
                ])->find($honorarioId);

                if (!$honorario) {
                    continue;
                }

                // No programar si ya está cerrado o sin saldo
                if (
                    $honorario->pagos->isNotEmpty() ||
                    $honorario->prontoPagos->isNotEmpty() ||
                    (int) $honorario->saldo_pendiente <= 0
                ) {
                    continue;
                }

                HonorarioPagoProgramado::updateOrCreate(
                    [
                        'honorario_mensual_rec_id' => $honorario->id,
                    ],
                    [
                        'fecha_programada' => $request->fecha_programada,
                        'user_id'          => Auth::id(),
                        'observacion'      => $request->observacion,
                    ]
                );

                $empresaId = $honorario->empresa_id;

                if (!$honorariosPorEmpresa->has($empresaId)) {
                    $honorariosPorEmpresa[$empresaId] = collect();
                }

                $honorariosPorEmpresa[$empresaId]->push(
                    $honorario->fresh()
                );
            }
        });

        $downloads = [];

        foreach ($honorariosPorEmpresa as $empresaId => $honorarios) {

            if ($honorarios->isEmpty()) {
                continue;
            }

            $token = (string) Str::uuid();

            Cache::put(
                "proximo_pago_honorarios_excel:$token",
                $honorarios,
                now()->addMinutes(10)
            );

            $downloads[] = [
                'url' => route('honorarios.mensual.proximo-pago.descargar', [
                    'token' => $token
                ]),
            ];
        }

        return response()->json([
            'ok' => true,
            'downloads' => $downloads,
        ]);
    }


    public function downloadPagoProgramadoExcel(string $token)
    {
        $honorariosProgramados = Cache::get("proximo_pago_honorarios_excel:$token");

        abort_if(
            !$honorariosProgramados || $honorariosProgramados->isEmpty(),
            404,
            'Exportación no disponible o expirada.'
        );

        Cache::forget("proximo_pago_honorarios_excel:$token");

        // =========================
        // OBTENER EMPRESA
        // =========================
        $honorario = $honorariosProgramados->first();
        $honorario->loadMissing('empresa');

        $nombreEmpresa = $honorario->empresa?->Nombre ?? 'Empresa';

        // Normalizar nombre empresa para archivo
        $nombreEmpresaArchivo = str_replace(
            ' ',
            '_',
            preg_replace('/[^A-Za-z0-9\s]/', '', $nombreEmpresa)
        );

        // =========================
        // NOMBRE ARCHIVO
        // =========================
        $fecha = now()->format('Y-m-d');

        $nombreArchivo = "{$nombreEmpresaArchivo}_honorarios_proximo_pago_{$fecha}.xlsx";

        return Excel::download(
            new HonorariosPagoMasivoExport($honorariosProgramados),
            $nombreArchivo
        );
    }



















}
