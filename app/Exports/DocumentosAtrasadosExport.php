<?php


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Este será el encargado de generar el Excel con todos los documentos ya vencidos.//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;


class DocumentosAtrasadosExport implements FromView
{
    protected $ventas;
    protected $compras;
    protected $fecha;

    public function __construct($ventas, $compras, $fecha)
    {
        $this->ventas = $ventas;
        $this->compras = $compras;
        $this->fecha = $fecha;
    }

    public function view(): View
    {
        return view('exports.documentos_atrasados_excel', [
            'ventas' => $this->ventas,
            'compras' => $this->compras,
            'fecha' => $this->fecha,
        ]);
    }
}
