<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HonorarioMensualRec;
use App\Services\Sii\HonorarioMensualRecParser;
use App\Models\HonorarioMensualRecTotal;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

class HonorarioMensualRecController extends Controller
{
    public function index()
    {
        $registros = HonorarioMensualRec::orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('fecha_emision')
            ->get();

        $total = HonorarioMensualRecTotal::orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        return view('boleta_mensual.index', compact('registros', 'total'));
    }


    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file',
        ]);

        Log::info('[IMPORT] Inicio importación');

        $parser = new HonorarioMensualRecParser(
            $request->file('archivo')
        );

        $preview = $parser->parse();

        // =========================
        // META DESDE SII
        // =========================
        Log::info('[IMPORT] Meta recibida desde SII', $preview['meta']);

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

        Log::info('[IMPORT] RUT normalizado', [
            'rut_archivo'    => $rutArchivo,
            'rut_formateado' => $rutFormateado,
        ]);

        // =========================
        // BUSCAR EMPRESA
        // =========================
        $empresa = Empresa::where('rut', $rutFormateado)->first();

        if (!$empresa) {
            Log::error('[IMPORT] Empresa no encontrada', [
                'rut_formateado' => $rutFormateado,
            ]);

            abort(422, 'Empresa no encontrada para el RUT informado por el SII.');
        }

        Log::info('[IMPORT] Empresa encontrada', [
            'empresa_id' => $empresa->id,
            'nombre'     => $empresa->Nombre,
            'rut'        => $empresa->rut,
        ]);

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

        Log::info('[IMPORT] Importación OK, redirigiendo a index');

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

            HonorarioMensualRec::updateOrCreate(
                [
                    'empresa_id'        => $empresaId,
                    'rut_contribuyente' => $meta['rut_contribuyente'],
                    'anio'              => $meta['anio'],
                    'mes'               => $meta['mes'],
                    'folio'             => $r['folio'],
                ],
                [
                    'razon_social'         => $meta['razon_social'],
                    'fecha_emision'        => $r['fecha_emision'],
                    'estado'               => $r['estado'],
                    'fecha_anulacion'      => $r['fecha_anulacion'],
                    'rut_emisor'           => $r['rut_emisor'],
                    'razon_social_emisor'  => $r['razon_social_emisor'],
                    'sociedad_profesional' => $r['sociedad_profesional'],
                    'monto_bruto'          => $r['monto_bruto'],
                    'monto_retenido'       => $r['monto_retenido'],
                    'monto_pagado'         => $r['monto_pagado'],
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





    public function panel(Request $request)
    {

        // Restricción de acceso solo para usuario 405
        $usuariosFinanzas = [1, 405];

        if (!in_array(Auth::id(), $usuariosFinanzas)) {
            abort(403, 'Acceso denegado. No tienes permiso para ingresar a este módulo.');
        }

        return view('boleta_mensual.panel_acceso.panel');

    }


}
