<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HonorarioMensualRec;
use App\Services\Sii\HonorarioMensualRecParser;

use Illuminate\Support\Facades\Log;

class HonorarioMensualRecController extends Controller
{
    public function index()
    {
        $registros = HonorarioMensualRec::orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('fecha_emision')
            ->get();

        return view('boleta_mensual.index', compact('registros'));
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

        foreach ($registros as $r) {

            HonorarioMensualRec::updateOrCreate(
                [
                    // Clave única lógica
                    'rut_contribuyente' => $meta['rut_contribuyente'],
                    'anio'              => $meta['anio'],
                    'mes'               => $meta['mes'],
                    'folio'             => $r['folio'],
                ],
                [
                    'razon_social'          => $meta['razon_social'],
                    'fecha_emision'         => $r['fecha_emision'],
                    'estado'                => $r['estado'],
                    'fecha_anulacion'       => $r['fecha_anulacion'],
                    'rut_emisor'            => $r['rut_emisor'],
                    'razon_social_emisor'   => $r['razon_social_emisor'],
                    'sociedad_profesional'  => $r['sociedad_profesional'],
                    'monto_bruto'           => $r['monto_bruto'],
                    'monto_retenido'        => $r['monto_retenido'],
                    'monto_pagado'          => $r['monto_pagado'],
                ]
            );
        }

        return redirect()
            ->route('honorarios.mensual.index')
            ->with('success', 'Honorarios mensuales guardados correctamente.');
    }

}
