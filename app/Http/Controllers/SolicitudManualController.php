<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\Vacacion;
use App\Models\Trabajador;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SolicitudManualController extends Controller
{
    public function formulario()
    {
        $trabajadores = Trabajador::all();
        return view('rrhh.solicitud_manual', compact('trabajadores'));
    }

    
    public function generarPDF(Request $request)
    {
        // Validación del formulario
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
            'tipo_dia' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'comentario' => 'nullable|string',
        ]);

        // Obtener el trabajador
        $trabajador = Trabajador::findOrFail($request->trabajador_id);

        // Obtener el próximo ID disponible en `vacacions`
        $vacacionId = Vacacion::max('id') + 1;

        // Crear solicitud en la tabla 'solicitudes'
        $solicitud = Solicitud::create([
            'trabajador_id' => $trabajador->id,
            'campo' => 'Vacaciones',
            'descripcion' => 'Solicitud de ' . $request->tipo_dia . ' del ' . $request->fecha_inicio . ' al ' . $request->fecha_fin,
            'estado' => 'aprobado',
            'tipo_dia' => $request->tipo_dia,
        ]);

        // Crear registro en la tabla 'vacacions' con el ID manualmente asignado
        $diasSolicitados = Carbon::parse($request->fecha_inicio)->diffInDays(Carbon::parse($request->fecha_fin)) + 1;
        $vacacion = Vacacion::create([
            'id' => $vacacionId, // Se asigna manualmente el ID
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'dias' => $diasSolicitados,
            'estado' => 'aprobado',
            'solicitud_id' => $solicitud->id,
        ]);

        // Generar el PDF
        $data = [
            'trabajador' => $trabajador,
            'solicitud' => $solicitud,
            'vacacion' => $vacacion,
        ];

        $rutaCarpeta = storage_path('app/solicitudes_manual/pdfs/');
        if (!file_exists($rutaCarpeta)) {
            mkdir($rutaCarpeta, 0777, true);
        }

        $pdf = PDF::loadView('pdf.solicitud_vacaciones', $data);
        $fileName = 'solicitud_manual_' . $vacacionId . '.pdf';
        $pdf->save($rutaCarpeta . $fileName);

        // Guardar la ruta en la base de datos
        $vacacion->archivo_admin = 'solicitudes_manual/pdfs/' . $fileName;
        $vacacion->save();

        return response()->download($rutaCarpeta . $fileName);
    }




}
