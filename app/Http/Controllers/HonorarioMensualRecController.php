<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HonorarioMensualRec;
use App\Services\Sii\HonorarioMensualRecParser;
use App\Models\HonorarioMensualRecTotal;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use App\Models\CobranzaCompra;
use Carbon\Carbon;



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
        $query = HonorarioMensualRec::with('empresa','cobranzaCompra',);

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
        // ORDEN + PAGINACIÓN
        // =========================
        $registros = $query
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('fecha_emision', 'desc')
            ->paginate(10)
            ->appends($request->query()); // 🔹 mantiene filtros al paginar

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

        Log::info('[STORE] Guardando honorarios', [
            'empresa_id' => $empresaId,
            'rut'        => $meta['rut_contribuyente'],
            'periodo'    => "{$meta['mes']}-{$meta['anio']}",
            'registros'  => count($registros),
        ]);

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
            // ESTADO FINANCIERO INICIAL
            // =========================
            $estadoFinancieroInicial = null;

            if ($cobranza && $cobranza->creditos !== null) {

                $fechaEmision = $r['fecha_emision']
                    ? Carbon::parse($r['fecha_emision'])
                    : null;

                if ($fechaEmision) {
                    $fechaVencimiento = $fechaEmision->copy()->addDays((int) $cobranza->creditos);

                    $estadoFinancieroInicial = $fechaVencimiento->isPast()
                        ? 'Vencido'
                        : 'Al día';
                }
            }

            HonorarioMensualRec::updateOrCreate(
                [
                    'empresa_id'        => $empresaId,
                    'rut_contribuyente' => $meta['rut_contribuyente'],
                    'anio'              => $meta['anio'],
                    'mes'               => $meta['mes'],
                    'rut_emisor'        => $rutEmisor,
                    'folio'             => $r['folio'],
                ],
                [
                    'razon_social'         => $meta['razon_social'],
                    'fecha_emision'        => $r['fecha_emision'],
                    'estado'               => $r['estado'], // estado SII
                    'fecha_anulacion'      => $r['fecha_anulacion'],
                    'razon_social_emisor'  => $r['razon_social_emisor'],
                    'sociedad_profesional' => $r['sociedad_profesional'],

                    'monto_bruto'          => $r['monto_bruto'],
                    'monto_retenido'       => $r['monto_retenido'],
                    'monto_pagado'         => $r['monto_pagado'],

                    // ✅ Finanzas
                    'saldo_pendiente'           => $r['monto_pagado'],
                    'estado_financiero_inicial' => $estadoFinancieroInicial,

                    // ✅ Relación proveedor (si existe)
                    'cobranza_compra_id'        => $cobranza?->id,
                ]
            );
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

        Log::info('[STORE] Honorarios guardados correctamente en BD');

        return redirect()
            ->route('honorarios.mensual.index')
            ->with('success', 'Honorarios mensuales guardados correctamente.');
    }



    // GUARDADO DE LOS ESTADOS MANUALES

    public function storeAbono(Request $request, HonorarioMensualRec $honorario)
    {
        Log::info('[storeAbono] Entrando', $request->all());

        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_abono' => 'required|date|before_or_equal:today',
        ], [
            'fecha_abono.before_or_equal' => 'La fecha del abono no debe ser futura.',
            'fecha_abono.required' => 'La fecha del abono es obligatoria.',
        ]);


        Log::info('[storeAbono] Validación OK', [
            'monto'              => $request->monto,
            'fecha_cruce'        => $request->fecha_cruce,
            'cobranza_compra_id' => $request->cobranza_compra_id,
        ]);

        // 1️⃣ Obtener saldo actual (desde BD)
        $saldoPendiente = $honorario->saldo_pendiente;

        // 2️⃣ Validar monto
        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El abono no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // 3️⃣ Registrar el abono
        $honorario->abonos()->create([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
        ]);

        // 4️⃣ Recalcular saldo pendiente
        $honorario->recalcularSaldoPendiente();

        // 5️⃣ Actualizar estado financiero
        $honorario->update([
            'estado_financiero' => 'Abono',
            'fecha_estado_financiero' => now(),
        ]);

        return back()->with('success', 'Abono registrado correctamente.');
    }




    public function storeCruce(Request $request, HonorarioMensualRec $honorario)
    {
        Log::info('[storeCruce] Entrando', $request->all());


        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe ser futura.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
        ]);

        Log::info('[storeCruce] Validación OK', [
            'monto'              => $request->monto,
            'fecha_cruce'        => $request->fecha_cruce,
            'cobranza_compra_id' => $request->cobranza_compra_id,
        ]);

        // 1️⃣ Saldo actual
        $saldoPendiente = $honorario->saldo_pendiente;

        // 2️⃣ Validar monto
        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El cruce no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // 3️⃣ Registrar cruce
        $honorario->cruces()->create([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
            'cobranza_compra_id' => $request->cobranza_compra_id,
        ]);

        // 4️⃣ Recalcular saldo
        $honorario->recalcularSaldoPendiente();

        // 5️⃣ Actualizar estado financiero
        $honorario->update([
            'estado_financiero' => 'Cruce',
            'fecha_estado_financiero' => now(),
        ]);

        return back()->with('success', 'Cruce registrado correctamente.');
    }



    public function storePago(Request $request, HonorarioMensualRec $honorario)
    {
        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
        ], [
            'fecha_pago.before_or_equal' => 'La fecha del pago no debe ser futura.',
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
        ]);

        // 🚫 Evitar duplicar pagos
        if ($honorario->pagos()->exists()) {
            return back()->withErrors([
                'fecha_pago' => 'Este honorario ya tiene un pago registrado.'
            ]);
        }

        // 1️⃣ Registrar el pago
        $honorario->pagos()->create([
            'fecha_pago' => $request->fecha_pago,
            'user_id' => Auth::id(),
        ]);

        // 2️⃣ Cerrar financieramente el honorario
        $honorario->update([
            'estado_financiero' => 'Pago',
            'fecha_estado_financiero' => now(),
            'saldo_pendiente' => 0,
        ]);

        return back()->with('success', 'Pago registrado correctamente.');
    }




    public function storeProntoPago(Request $request, HonorarioMensualRec $honorario)
    {
        Log::info('[storeProntoPago] Entrando', $request->all());

        $request->validate([
            'fecha_pronto_pago' => 'required|date|before_or_equal:today',
        ], [
            'fecha_pronto_pago.before_or_equal' => 'La fecha del pronto pago no debe ser futura.',
            'fecha_pronto_pago.required' => 'La fecha del pronto pago es obligatoria.',
        ]);


        Log::info('[storeProntoPago] Validación OK', [
            'fecha_pronto_pago' => $request->fecha_pronto_pago,
        ]);



        // 🚫 Evitar duplicados
        if ($honorario->prontoPagos()->exists()) {
            return back()->withErrors([
                'fecha_pronto_pago' => 'Este honorario ya tiene un pronto pago registrado.'
            ]);
        }

        // 1️⃣ Registrar pronto pago
        $honorario->prontoPagos()->create([
            'fecha_pronto_pago' => $request->fecha_pronto_pago,
            'user_id' => Auth::id(),
        ]);

        // 2️⃣ Cerrar financieramente el honorario
        $honorario->update([
            'estado_financiero' => 'Pronto pago',
            'fecha_estado_financiero' => now(),
            'saldo_pendiente' => 0,
        ]);

        return back()->with('success', 'Pronto pago registrado correctamente.');
    }



    public function storeEstado(Request $request)
    {

        Log::info('[storeEstado] Request recibido', $request->all());


        $honorario = HonorarioMensualRec::findOrFail($request->honorario_id);

        Log::info('[storeEstado] Estado financiero', [
            'estado_financiero' => $request->estado_financiero,
        ]);

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








}
