<?php

namespace App\Http\Controllers;

use App\Models\DocumentoFinanciero;
use Illuminate\Http\Request;

class AbonoController extends Controller
{
    //
    public function index(DocumentoFinanciero $documento)
    {
        // Traer todos los abonos ordenados por fecha
        $abonos = $documento->abonos()->orderBy('fecha_abono', 'asc')->get();

        // Calcular el total abonado y el saldo pendiente
        $totalAbonado = $abonos->sum('monto');
        $saldoPendiente = $documento->monto_total - $totalAbonado;

        return view('abonos.index', compact('documento', 'abonos', 'totalAbonado', 'saldoPendiente'));
    }
}
