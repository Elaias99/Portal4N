<?php

namespace App\Http\Controllers;

use App\Services\ZplLabelService;
use Illuminate\Http\Request;
use App\Exports\LabelsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EtiquetaGrandeImport;
use App\Services\EtiquetaGrandeService;
use ZipArchive;
use Illuminate\Support\Str;


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

        $rows = ($import->rows ?? collect())->values();

        // 1️⃣ Agrupar TODAS las filas por ID (una etiqueta = un ID)
        $etiquetas = $rows->groupBy('id')->values();

        // 2️⃣ Partir en bloques de 95 etiquetas
        $lotes = $etiquetas->chunk(95);

        $zipName = 'etiquetas_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0777, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el ZIP');
        }

        foreach ($lotes as $index => $loteEtiquetas) {

            $zpl = '';

            foreach ($loteEtiquetas as $etiquetaRows) {
                // Cada $etiquetaRows contiene TODAS las filas de un ID
                $zpl .= $service->makeLabel($etiquetaRows);
            }

            // lote_1.zpl, lote_2.zpl, etc.
            $filenameInZip = 'lote_' . ($index + 1) . '.zpl';
            $zip->addFromString($filenameInZip, $zpl);
        }

        $zip->close();

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }









}



