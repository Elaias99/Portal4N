<?php

namespace App\Http\Controllers;

use App\Models\Talla;
use App\Models\Trabajador;
use App\Models\TipoVestimenta;
use Illuminate\Http\Request;

class TallaController extends Controller
{
    public function index()
    {
        // Obtiene todas las tallas con sus relaciones
        $tallas = Talla::with(['trabajador', 'tipoVestimenta'])->get();
        return view('tallas.index', compact('tallas'));
    }

    public function create()
    {
        // Obtiene todos los trabajadores y tipos de vestimenta para el formulario
        $trabajadores = Trabajador::all();
        $tiposVestimenta = TipoVestimenta::all();
        return view('tallas.create', compact('trabajadores', 'tiposVestimenta'));
    }

    public function store(Request $request)
    {

        dd($request->tallas);


        // Validación adaptada para múltiples tallas
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadors,id',
            'tallas' => 'required|array',
            'tallas.*.talla' => 'required_with:tallas.*.custom|nullable|string|max:10', // Talla seleccionada o personalizada
            'tallas.*.custom' => 'nullable|string|max:10', // Campo personalizado solo si "Otro" es seleccionado
        ]);

        foreach ($request->tallas as $tipoVestimentaId => $detalle) {
            // Verifica si se seleccionó "otro" y si hay un valor en el campo custom
            if ($detalle['talla'] === 'otro' && !empty($detalle['custom'])) {
                $talla = $detalle['custom'];
            } else {
                $talla = $detalle['talla'];
            }
        
            // Asegúrate de que el valor no sea nulo ni inválido
            if (!$talla || strlen($talla) > 10) {
                return back()->withErrors(['tallas' => 'Debe ingresar una talla válida.'])->withInput();
            }
        
            // Guarda la talla en la base de datos
            Talla::create([
                'trabajador_id' => $request->trabajador_id, // O $id en el método update
                'tipo_vestimenta_id' => $tipoVestimentaId,
                'talla' => $talla,
            ]);
        }
        
    }


    public function edit($id)
    {
        // Obtiene la talla a editar
        $talla = Talla::findOrFail($id);
        $trabajadores = Trabajador::all();
        $tiposVestimenta = TipoVestimenta::all();

        return view('tallas.edit', compact('talla', 'trabajadores', 'tiposVestimenta'));
    }

    public function update(Request $request, $id)
    {
        // Validación similar al método `store`
        $request->validate([
            'tallas' => 'required|array',
            'tallas.*.talla' => 'required_with:tallas.*.custom|string|max:10',
            'tallas.*.custom' => 'nullable|string|max:10',
        ]);

        // Elimina las tallas existentes del trabajador
        Talla::where('trabajador_id', $id)->delete();

        // Asigna las nuevas tallas
        foreach ($request->tallas as $tipoVestimentaId => $detalle) {
            $talla = $detalle['talla'] === 'otro' ? $detalle['custom'] : $detalle['talla'];

            Talla::create([
                'trabajador_id' => $id,
                'tipo_vestimenta_id' => $tipoVestimentaId,
                'talla' => $talla,
            ]);
        }

        return redirect('tallas')->with('success', 'Tallas actualizadas exitosamente.');
    }


    public function destroy($id)
    {
        // Elimina la talla
        $talla = Talla::findOrFail($id);
        $talla->delete();

        return redirect()->route('tallas.index')->with('success', 'Talla eliminada exitosamente.');
    }
}
