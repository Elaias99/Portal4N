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
        $data = json_decode(base64_decode($request->input('data')), true);

        foreach ($data['meses'] as $mes) {
            HonorarioResumenAnual::updateOrCreate(
                [
                    'rut_contribuyente' => $data['rut_contribuyente'],
                    'anio'              => $data['anio'],
                    'mes'               => $this->mapMesNumero($mes['mes_nombre']),
                ],
                [
                    'razon_social'      => $data['razon_social'],
                    'mes_nombre'        => $mes['mes_nombre'],
                    'folio_inicial'     => $mes['folio_inicial'],
                    'folio_final'       => $mes['folio_final'],
                    'boletas_vigentes'  => $mes['boletas_vigentes'],
                    'boletas_nulas'     => $mes['boletas_nulas'],
                    'honorario_bruto'   => $mes['honorario_bruto'],
                    'retenciones'       => $mes['retenciones'],
                    'total_liquido'     => $mes['total_liquido'],
                ]
            );
        }

        return redirect()
            ->route('honorarios.resumen.index')
            ->with('success', 'Resumen anual importado correctamente.');
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



}
