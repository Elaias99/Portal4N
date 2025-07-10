<?php

namespace App\Http\Controllers;

use App\Exports\BancosExport;
use App\Exports\TipoCuentaExport;
use App\Exports\TipoDocumentoExport;
use Maatwebsite\Excel\Facades\Excel;


use Illuminate\Http\Request;

class ExportacionController extends Controller
{
    //
    public function exportarBancos()
    {
        return Excel::download(new BancosExport, 'bancos.xlsx');
    }

    public function exportarTipoCuentas()
    {
        return Excel::download(new TipoCuentaExport, 'tipos_cuenta.xlsx');
    }

    public function exportarTipoDocumentos()
    {
        return Excel::download(new TipoDocumentoExport, 'tipos_documento.xlsx');
    }

}
