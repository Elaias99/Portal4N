<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asistencia;
use App\Models\Trabajador;
use Carbon\Carbon;

class AsistenciaController extends Controller {


    
    public function index() {
        $empleados = Trabajador::where('situacion_id', 1)->with('asistencias')->get();
        return view('asistencia.index', compact('empleados'));
    }

    public function store(Request $request) {
        foreach ($request->asistencias as $trabajador_id => $asistio) {
            Asistencia::updateOrCreate(
                ['trabajador_id' => $trabajador_id, 'fecha' => Carbon::now()->toDateString()],
                ['asistio' => $asistio]
            );
        }

        return redirect()->back()->with('success', 'Asistencia guardada correctamente');
    }
}
