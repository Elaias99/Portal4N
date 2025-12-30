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
        // persistencia luego del preview
    }
}
