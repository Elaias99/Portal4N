<?php

namespace App\Http\Controllers;
use App\Models\HonorarioResumenAnual;
use Illuminate\Http\Request;
use App\Services\Sii\BteResumenAnualParser;

class HonorarioResumenAnualController extends Controller
{
    //
    public function index()
    {
        $registros = HonorarioResumenAnual::orderBy('anio', 'desc')
            ->orderBy('mes', 'asc')
            ->get();

        return view('boleta_honorario.index', compact('registros'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file'
        ]);

        $parser = new BteResumenAnualParser($request->file('archivo'));
        $resultado = $parser->parse();

        return view('boleta_honorario.index', [
            'registros' => collect(), // aún no BD
            'preview'   => $resultado
        ]);
    }

    public function store(Request $request)
    {
        $data = json_decode(
            base64_decode($request->input('data')),
            true
        );

        $registros = $data['registros'];

        // Filtrar vigentes / anuladas
        $vigentes = array_filter($registros, fn($r) => $r['estado'] === 'VIGENTE');
        $anuladas = array_filter($registros, fn($r) => $r['estado'] !== 'VIGENTE');

        // Folios
        $folios = array_column($registros, 'folio');

        $folioInicial = !empty($folios) ? min($folios) : 0;
        $folioFinal   = !empty($folios) ? max($folios) : 0;

        // Montos (solo vigentes)
        $honorarioBruto = array_sum(array_column($vigentes, 'monto_bruto'));
        $retenciones    = array_sum(array_column($vigentes, 'monto_retenido'));
        $totalLiquido   = array_sum(array_column($vigentes, 'monto_pagado'));

        HonorarioResumenAnual::updateOrCreate(
            [
                'rut_contribuyente' => $data['rut_contribuyente'],
                'anio'              => $data['anio'],
                'mes'               => $data['mes'],
            ],
            [
                'razon_social'      => $data['razon_social'],
                'mes_nombre'        => $this->mapMesNumeroInverso($data['mes']),
                'folio_inicial'     => $folioInicial,
                'folio_final'       => $folioFinal,
                'boletas_vigentes'  => count($vigentes),
                'boletas_nulas'     => count($anuladas),
                'honorario_bruto'   => $honorarioBruto,
                'retenciones'       => $retenciones,
                'total_liquido'     => $totalLiquido,
            ]
        );

        return redirect()
            ->route('honorarios.resumen.index')
            ->with('success', 'Resumen mensual importado correctamente.');
    }


    protected function mapMesNumero(string $mes): int
    {
        return [
            'ENERO'       => 1,
            'FEBRERO'     => 2,
            'MARZO'       => 3,
            'ABRIL'       => 4,
            'MAYO'        => 5,
            'JUNIO'       => 6,
            'JULIO'       => 7,
            'AGOSTO'      => 8,
            'SEPTIEMBRE'  => 9,
            'OCTUBRE'     => 10,
            'NOVIEMBRE'   => 11,
            'DICIEMBRE'   => 12,
        ][$mes] ?? 0;
    }

    protected function mapMesNumeroInverso(int $mes): string
    {
        return [
            1  => 'ENERO',
            2  => 'FEBRERO',
            3  => 'MARZO',
            4  => 'ABRIL',
            5  => 'MAYO',
            6  => 'JUNIO',
            7  => 'JULIO',
            8  => 'AGOSTO',
            9  => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ][$mes] ?? '';
    }




}
