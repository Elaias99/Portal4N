<?php

namespace App\Services\Ventas;

use App\Imports\DocumentosImport;
use App\Models\Empresa;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class DocumentoFinancieroImportService
{
    public function execute(UploadedFile $file): DocumentosImport
    {
        $filename = $file->getClientOriginalName();

        $rut = null;
        if (preg_match('/(\d{7,8}-[0-9Kk])/', $filename, $matches)) {
            $rut = $this->normalizarRut($matches[1]);
        }

        $empresa = null;
        if ($rut) {
            $empresa = Empresa::whereRaw(
                "REPLACE(REPLACE(rut, '.', ''), '-', '-') = ?",
                [$rut]
            )->first();
        }

        $import = new DocumentosImport($empresa?->id);

        Excel::import($import, $file);
        $import->afterImport();

        return $import;
    }

    private function normalizarRut(?string $rut): ?string
    {
        if (!$rut) {
            return null;
        }

        $rut = preg_replace('/[^0-9kK-]/', '', $rut);

        return strtoupper($rut);
    }
}