<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoCompra;
use App\Imports\ComprasImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Exports\DocumentoCompraExport;

class DocumentoCompraController extends Controller
{
    /**
     * 📄 Muestra todos los registros de compras
     */
    public function index()
    {
        $documentosCompras = DocumentoCompra::latest()->paginate(10);
        return view('cobranzas.finanzas_compras.index', compact('documentosCompras'));
        
    }

    /**
     * 📤 Importa el archivo Excel RCV_COMPRAS
     */
    public function import(Request $request)
    {
        
        $request->validate([
            'file' => 'required|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();




        // 🔍 Extraer el RUT del nombre del archivo (ej: RCV_COMPRA_REGISTRO_77639015-1_202510)
        $rut = null;
        if (preg_match('/(\d{7,8}-[0-9Kk])/', $filename, $matches)) {
            $rut = $matches[1];
        }

        // 🧩 Normalizar formato: quitar puntos, guiones y espacios
        $rutLimpio = null;
        if ($rut) {
            $rutLimpio = str_replace(['.', '-', ' '], '', $rut);
        }

        // 🏢 Buscar empresa con ese RUT normalizado
        $empresa = null;
        if ($rutLimpio) {
            $empresa = \App\Models\Empresa::whereRaw("
                REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?
            ", [$rutLimpio])->first();
        }

        // 🟢 Si no se encontró empresa, evita romper el flujo
        if (!$empresa) {
            return redirect()->back()->with('error', "No se encontró ninguna empresa asociada al RUT {$rut} (archivo: {$filename}).");
        }

        // ✅ Importar vinculando a la empresa encontrada
        $import = new ComprasImport($empresa->id);
        Excel::import($import, $request->file('file'));
        
        $totalImportados = \App\Models\DocumentoCompra::where('empresa_id', $empresa->id)
            ->whereDate('created_at', today())
            ->count();

        $totalDuplicados = count($import->duplicados);

        // 🟢 Caso 1: Importación exitosa, sin duplicados
        if ($totalImportados > 0 && $totalDuplicados === 0) {
            return redirect()->route('finanzas_compras.index')->with('success', "Archivo importado correctamente.");
        }

        // 🟡 Caso 2: Todo duplicado (no se importó nada)
        if ($totalImportados === 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'warning' => "Todos los registros del archivo ya existían. No se importó ningún registro nuevo.",
                'detalles_errores' => $import->duplicados
            ]);
        }

        // 🟡 Caso 3: Mezcla (algunos nuevos y otros duplicados)
        if ($totalImportados > 0 && $totalDuplicados > 0) {
            return redirect()->route('finanzas_compras.index')->with([
                'success' => "Se importaron {$totalImportados} registros nuevos.",
                'warning' => "Se detectaron {$totalDuplicados} folios duplicados que no fueron importados.",
                'detalles_errores' => $import->duplicados
            ]);
        }

        // 🔴 Caso 4: Archivo vacío o sin registros válidos
        return redirect()->route('finanzas_compras.index')->with('error', 'No se encontraron registros válidos para importar.');
    }



    public function export()
    {
        return Excel::download(new DocumentoCompraExport, 'documentos_compras.xlsx');
    }


}
