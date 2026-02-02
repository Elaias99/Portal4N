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



class HonorarioMensualRecController extends Controller
{
    

    // =========================
    // PANEL DE ACCESO A LOS DOS MÓDULOS DE BOLETA
    // =========================

    public function panel(Request $request)
    {

        // Restricción de acceso solo para usuario 405
        $usuariosFinanzas = [1, 405];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        return view('boleta_mensual.panel_acceso.panel');

    }



    public function index(Request $request)
    {
        $query = HonorarioMensualRec::with('empresa', 'cobranzaCompra');

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
        if ($request->filled('saldo_tipo') && $request->filled('saldo_monto')) {

            $monto = (int) $request->saldo_monto;

            if ($request->saldo_tipo === 'pendiente') {
                $query->where('saldo_pendiente', '>=', $monto);
            }

            if ($request->saldo_tipo === 'original') {
                $query->where('monto_pagado', '>=', $monto);
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

                        // 🔁 EXISTE → solo actualizar datos SII
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

                            // ❌ NO tocar:
                            // saldo_pendiente
                            // estado_financiero
                            // servicio_manual
                        ]);

                    } else {

                        // 🆕 NUEVO → inicializar capa financiera
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

                            // ✅ SOLO AQUÍ
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

        // 📌 Snapshot previo
        $estadoAnterior = $honorario->estado_financiero_final;
        $saldoAnterior  = $honorario->saldo_pendiente;

        if ($montoAbono > $saldoAnterior) {
            return back()->withErrors([
                'monto_abono' => 'El abono no puede ser mayor al saldo pendiente actual.'
            ]);
        }

        // 1️⃣ Registrar abono
        $honorario->abonos()->create([
            'monto'       => $montoAbono,
            'fecha_abono' => $request->fecha_abono,
        ]);

        // 2️⃣ Recalcular saldo
        $honorario->recalcularSaldoPendiente();

        // 3️⃣ Actualizar estado
        $honorario->update([
            'estado_financiero'       => 'Abono',
            'fecha_estado_financiero' => now(),
        ]);

        $honorario->refresh();

        // 4️⃣ Registrar movimiento
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

                // 🔒 Si ya tiene pago, se ignora
                if ($honorario->pagos()->exists()) {
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













}
