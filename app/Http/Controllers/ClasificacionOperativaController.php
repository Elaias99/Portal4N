<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comuna;

class ClasificacionOperativaController extends Controller
{
    //
    public function index(Request $request)
    {
        $regionId = $request->input('region');

        $comunasQuery = \App\Models\Comuna::with(
            'clasificacionOperativa.zona.zonaMadre',
            'clasificacionOperativa.tipoZona',
            'clasificacionOperativa.subzona',
            'clasificacionOperativa.proveedor', // 🔹 Aquí va el proveedor
            'region'
        )->orderBy('Nombre');

        if ($regionId) {
            $comunasQuery->where('region_id', $regionId);
        }

        $comunas = $comunasQuery->get();
        $regiones = \App\Models\Region::orderBy('id')->get();

        return view('clasificacion_operativa.index', compact('comunas', 'regiones', 'regionId'));
    }



    public function edit($comuna_id)
    {
        $comuna = \App\Models\Comuna::with('clasificacionOperativa')->findOrFail($comuna_id);
        $comunasTodas = \App\Models\Comuna::orderBy('Nombre')->get();
        $zonas = \App\Models\Zona::all();
        $tiposZona = \App\Models\TipoZona::all();
        $subzonas = \App\Models\Subzona::all();
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get();

        return view('clasificacion_operativa.edit', compact('comuna', 'zonas', 'tiposZona', 'subzonas', 'comunasTodas', 'proveedores'));
    }


    public function update(Request $request, $comuna_id)
    {
        $validated = $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'tipo_zona_id' => 'required|exists:tipos_zona,id',
            'subzona_id' => 'required|exists:subzonas,id',
            'comuna_matriz' => 'nullable|string|max:255',
            'proveedor_id' => 'nullable|exists:proveedores,id',
        ]);

        \App\Models\ComunaClasificacionOperativa::updateOrCreate(
            ['comuna_id' => $comuna_id],
            $validated + ['comuna_id' => $comuna_id]
        );

        return redirect()->route('clasificacion-operativa.index')->with('success', 'Clasificación actualizada.');
    }





}
