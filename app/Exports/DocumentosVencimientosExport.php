<?php

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Este archivo se usa para el reporte semanal de documentos por vencer (es decir, los que caen dentro del rango lunes–domingo actual).//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DocumentosVencimientosExport implements FromView
{
    protected $ventas;
    protected $compras;
    protected $inicio;
    protected $fin;

    public function __construct($ventas, $compras, $inicio, $fin)
    {
        $this->ventas = $ventas;
        $this->compras = $compras;
        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    public function view(): View
    {
        return view('exports.documentos_vencimientos_excel', [
            'ventas' => $this->ventas,
            'compras' => $this->compras,
            'inicio' => $this->inicio,
            'fin' => $this->fin,
        ]);
    }
}
