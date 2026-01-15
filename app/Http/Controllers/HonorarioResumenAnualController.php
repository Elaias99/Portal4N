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

        $rut   = $data['rut_contribuyente'];
        $razon = $data['razon_social'];
        $anio  = $data['anio'];

        foreach ($data['resumen_mensual'] as $mes) {

            HonorarioResumenAnual::updateOrCreate(
                [
                    'rut_contribuyente' => $rut,
                    'anio'              => $anio,
                    'mes'               => $mes['mes'],
                ],
                [
                    'razon_social'      => $razon,
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
