<?php

namespace App\Services;

use App\Models\Trabajador;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrabajadorExport;

class TrabajadorExportService
{
    // Método para exportar empleados en formato PDF
    public function exportPdf()
    {
        $empleados = Trabajador::all();
        $pdf = Pdf::loadView('empleados.pdf', compact('empleados'))->setPaper('a4', 'landscape');
        return $pdf->download('empleados.pdf');
    }

    // Método para exportar empleados en formato Excel
    public function exportExcel()
    {
        return Excel::download(new TrabajadorExport, 'empleados.xlsx');
    }

    // Método para exportar la cotización de un empleado en PDF
    public function exportCotizacionPdf($trabajador)
    {
        // Calcula la cotización del trabajador
        $cotizacion = $trabajador->calcularCotizacion();

        // Carga la vista y genera el PDF
        $pdf = Pdf::loadView('empleados.exportpdf', compact('trabajador', 'cotizacion'))
                  ->setPaper('a4', 'portrait');

        // Retorna el PDF para descargarlo
        return $pdf->download('cotizacion_trabajador_' . $trabajador->Nombre . '.pdf');
    }
}
