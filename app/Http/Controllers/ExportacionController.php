<?php

namespace App\Http\Controllers;

use App\Exports\BancosExport;
use App\Exports\CentroCostoExport;

use App\Exports\EmpresaExport;

use App\Exports\FormaPagoExport;
use App\Exports\PlazPagoExport;
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

    public function exportarCentroCosto()
    {
        return Excel::download(new CentroCostoExport, 'centro_costo.xlsx');
    }

    public function exportarFormaPago()
    {
        return Excel::download(new FormaPagoExport, 'forma_pago.xlsx');
    }

    public function exportarPlazoPago()
    {
        return Excel::download(new PlazPagoExport, 'plazo_pago.xlsx');
    }

    public function exportarEmpresa()
    {
        return Excel::download(new EmpresaExport, 'empresas.xlsx');
    }

}
