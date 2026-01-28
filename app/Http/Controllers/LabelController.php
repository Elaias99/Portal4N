<?php

namespace App\Http\Controllers;

use App\Services\ZplLabelService;
use Illuminate\Http\Request;
use App\Exports\LabelsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EtiquetaGrandeImport;
use App\Services\EtiquetaGrandeService;

class LabelController extends Controller
{


    public function uploadExcel(Request $request, ZplLabelService $service)
    {
        // GET → mostrar formulario
        if ($request->isMethod('get')) {
            return view('labels.excel');
        }

        // POST → procesar Excel
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $rows = Excel::toCollection(null, $request->file('file'))[0];

        $zpl = '';

        foreach ($rows->skip(1) as $row) {
            $zpl .= $service->makeLabel([
                'QR'        => $row[0] ?? '',
                'Atencion'  => $row[1] ?? '',
                'Direccion' => $row[2] ?? '',
                'Comuna'    => $row[3] ?? '',
            ]);
        }

        return response($zpl, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="etiquetas_' . now()->format('Ymd_His') . '.zpl"',
        ]);
    }


    public function downloadTemplate()
    {
        return Excel::download(
            new LabelsTemplateExport(),
            'plantilla_etiquetas_zebra.xlsx'
        );
    }










    public function panel()
    {
        return view('labels.panel');
    }




















    public function uploadEtiquetaGrande(Request $request, EtiquetaGrandeService $service)
    {
        if ($request->isMethod('get')) {
            return view('labels.etiqueta-grande');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $import = new EtiquetaGrandeImport();
        Excel::import($import, $request->file('file'));

        $rows = $import->rows ?? collect();

        // Con WithHeadingRow, normalmente los encabezados quedan en minúscula: id, id_bulto, etc.
        $groups = $rows->groupBy('id');

        $zpl = '';

        foreach ($groups as $group) {
            $zpl .= $service->makeLabel($group);
        }

        return response($zpl, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="etiquetas_grandes_' . now()->format('Ymd_His') . '.zpl"',
        ]);
    }






}



