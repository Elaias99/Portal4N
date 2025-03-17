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

        // Obtener todas las firmas disponibles en el almacenamiento
        $firmaDirectory = storage_path('app/Firmas/');
        $firmas = file_exists($firmaDirectory) ? array_diff(scandir($firmaDirectory), ['.', '..']) : [];

        return view('rrhh.solicitud_manual', compact('trabajadores', 'firmas'));
    }


    
    public function generarPDF(Request $request)
    {
        // Validar formulario
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
            'tipo_dia' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'dias' => 'required|integer|min:1',
            'firma' => 'required|string', // Validar que la firma fue seleccionada
            'comentario' => 'nullable|string',
        ]);

        // Obtener trabajador
        $trabajador = Trabajador::findOrFail($request->trabajador_id);
        $vacacionId = Vacacion::max('id') + 1;

        // Crear solicitud
        $solicitud = Solicitud::create([
            'trabajador_id' => $trabajador->id,
            'campo' => 'Vacaciones',
            'descripcion' => 'Solicitud de ' . $request->tipo_dia . ' del ' . $request->fecha_inicio . ' al ' . $request->fecha_fin,
            'estado' => 'aprobado',
            'tipo_dia' => $request->tipo_dia,
        ]);

        // Obtener días ingresados manualmente
        $diasSolicitados = $request->dias;

        // Crear vacación
        $vacacion = Vacacion::create([
            'id' => $vacacionId,
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'dias' => $diasSolicitados,
            'estado' => 'aprobado',
            'solicitud_id' => $solicitud->id,
        ]);

        // Ruta de la firma seleccionada
        $firmaPath = storage_path('app/Firmas/' . $request->firma);

        // Generar el PDF
        $data = [
            'trabajador' => $trabajador,
            'solicitud' => $solicitud,
            'vacacion' => $vacacion,
            'firmaPath' => file_exists($firmaPath) ? $firmaPath : null, // Solo si la firma existe
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
