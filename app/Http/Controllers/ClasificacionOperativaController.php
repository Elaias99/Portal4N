<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comuna;
use App\Models\FrecuenciaDistribucion;
use Carbon\Carbon;

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
            'clasificacionOperativa.proveedor',
            'clasificacionOperativa.frecuenciaDistribucion.dias',
            'region'
        )->orderBy('Nombre');

        if ($regionId) {
            $comunasQuery->where('region_id', $regionId);
        }

        $comunas = $comunasQuery->get();

        foreach ($comunas as $comuna) {
            $dias = [];

            $frecuencia = optional(optional($comuna->clasificacionOperativa)->frecuenciaDistribucion);

            if ($frecuencia && $frecuencia->dias) {
                $dias = $frecuencia->dias->pluck('dia_semana')->toArray();
            }

            $comuna->frecuencia_texto = $this->formatearDias($dias);

            // Calcular fecha próxima de entrega (solo si hay frecuencia)
            if (!empty($dias)) {
                $hoy = Carbon::now()->locale('es')->dayName; // Ej: "martes"
                $diaFormateado = ucfirst($hoy); // Carbon da minúsculas, lo pasamos a "Martes"
                $diasHasta = $this->calcularDiasHastaProximaEntrega($dias, $diaFormateado);
                $comuna->proxima_entrega = Carbon::now()->addDays($diasHasta)->format('d-m-Y');

            } else {
                $comuna->proxima_entrega = null;
            }
        }

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

        $proveedorId = optional($comuna->clasificacionOperativa)->proveedor_id;

        $frecuencia = null;
        $frecuenciaDias = [];

        
        if ($proveedorId) {
            $frecuencia = FrecuenciaDistribucion::with('dias')
                ->where('comuna_id', $comuna->id)
                ->where('proveedor_id', $proveedorId)
                ->first();

            $frecuenciaDias = $frecuencia ? $frecuencia->dias->pluck('dia_semana')->toArray() : [];
        }

        

        return view('clasificacion_operativa.edit', compact('comuna', 'zonas', 'tiposZona', 'subzonas', 'comunasTodas', 'proveedores', 'frecuenciaDias'));
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

        $frecuencia = FrecuenciaDistribucion::updateOrCreate(
            [
                'comuna_id' => $comuna_id,
                'proveedor_id' => $validated['proveedor_id'],
            ]
        );


        // Limpiamos días anteriores (por si hubo cambios)
        $frecuencia->dias()->delete();

        // Guardamos los nuevos días seleccionados
        $diasSeleccionados = $request->input('frecuencia_dias', []);

        foreach ($diasSeleccionados as $dia) {
            $frecuencia->dias()->create(['dia_semana' => $dia]);
        }



        return redirect()->route('clasificacion-operativa.index')->with('success', 'Clasificación actualizada.');
    }



    private function formatearDias(array $dias)
    {
        $orden = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $diasOrdenados = array_values(array_intersect($orden, $dias));

        if (count($diasOrdenados) === 7) return 'Todos los días';
        if ($diasOrdenados === array_slice($orden, 0, 5)) return 'Lunes a Viernes';
        if (count($diasOrdenados) === 2) return implode(' y ', $diasOrdenados);
        return implode(', ', $diasOrdenados);
    }

    private function calcularDiasHastaProximaEntrega(array $diasEntrega, string $diaActual): int
    {
        $orden = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $diaActualIndex = array_search($diaActual, $orden);
        if ($diaActualIndex === false) return 0;

        // Convertimos días de entrega a índices
        $indicesEntrega = array_map(fn($dia) => array_search($dia, $orden), $diasEntrega);
        $indicesEntrega = array_filter($indicesEntrega, fn($i) => $i !== false);
        sort($indicesEntrega);

        foreach ($indicesEntrega as $index) {
            if ($index >= $diaActualIndex) {
                return $index - $diaActualIndex;
            }
        }

        // Si no hay días restantes esta semana, calcula para la próxima
        return (7 - $diaActualIndex) + $indicesEntrega[0];
    }







}
