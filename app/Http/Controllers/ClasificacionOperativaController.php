<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FrecuenciaDistribucion;
use Carbon\Carbon;
use App\Models\RutaGeografica;
use App\Models\OrdenTransporte;

use App\Exports\ClasificacionOperativaExport;
use Maatwebsite\Excel\Facades\Excel;


class ClasificacionOperativaController extends Controller
{
    //
    public function index(Request $request)
    {
        $comunasQuery = \App\Models\Comuna::with([
            'clasificacionOperativa.zona.zonaMadre',
            'clasificacionOperativa.tipoZona',
            'clasificacionOperativa.subzona',
            'clasificacionOperativa.proveedor',
            'clasificacionOperativa.zonaRutaGeografica',
            'clasificacionOperativa.zonaRutaGeografica.origen',
            'clasificacionOperativa.zonaRutaGeografica.destino',
            'clasificacionOperativa.cobertura',
            'clasificacionOperativa.provincia',
            'clasificacionOperativa.codigoiata',
            'clasificacionOperativa.frecuenciaDistribucion.dias',
            'region',
            'ordenTransporte'
        ])->orderBy('Nombre');

   
        $this->aplicarFiltros($comunasQuery, $request);

        $comunas = $comunasQuery->get();

        //  post-procesamiento de días y próxima entrega
        foreach ($comunas as $comuna) {
            $dias = optional(optional($comuna->clasificacionOperativa)->frecuenciaDistribucion->dias)->pluck('dia_semana')->toArray() ?? [];

            $comuna->frecuencia_texto = $this->formatearDias($dias);

            $comuna->proxima_entrega = !empty($dias)
                ? Carbon::now()->addDays($this->calcularDiasHastaProximaEntrega($dias, ucfirst(Carbon::now()->locale('es')->dayName)))->format('d-m-Y')
                : null;
        }

        $regiones = \App\Models\Region::orderBy('id')->get();

        $regionesConComunas = $regiones->filter(function ($region) use ($comunas) {
            return $comunas->where('region_id', $region->id)->isNotEmpty();
        });

        return view('clasificacion_operativa.index', [
            'comunas' => $comunas,
            'regiones' => $regionesConComunas,
            'regionId' => $request->input('region'),
            'zonas' => \App\Models\Zona::all(),
            'subzonas' => \App\Models\Subzona::all(),
            'coberturas' => \App\Models\Cobertura::all(),
            'filtros' => $request->only(['comuna', 'proveedor', 'zona', 'subzona', 'cobertura']),
        ]);

    }


    public function edit($comuna_id)
    {
        $comuna = \App\Models\Comuna::with('clasificacionOperativa', 'ordenTransporte')->findOrFail($comuna_id);

        $comunasTodas = \App\Models\Comuna::orderBy('Nombre')->get();
        $zonas = \App\Models\Zona::all();
        $tiposZona = \App\Models\TipoZona::all();
        $subzonas = \App\Models\Subzona::all();
        $proveedores = \App\Models\Proveedor::orderBy('razon_social')->get();
        $rutasGeograficas = \App\Models\RutaGeografica::orderBy('nombre')->get();

        $coberturas = \App\Models\Cobertura::orderBy('nombre')->get();

        $provincias = \App\Models\Provincia::orderBy('nombre')->get();

        $codigoiatas = \App\Models\CodigoIata::orderBy('cod_iata')->get();



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

        

        return view('clasificacion_operativa.edit', compact('comuna', 'zonas', 'tiposZona', 'subzonas', 'comunasTodas', 'proveedores', 'frecuenciaDias','rutasGeograficas','coberturas','provincias','codigoiatas'));
    }


    public function update(Request $request, $comuna_id)
    {

        // dd($request->all());

        $validated = $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            // 'tipo_zona_id' => 'required|exists:tipos_zona,id',
            'subzona_id' => 'required|exists:subzonas,id',
            'comuna_matriz' => 'nullable|string|max:255',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'zona_ruta_geografica_id' => 'required|exists:zona_ruta_geograficas,id',
            'cobertura_id' => 'nullable|exists:coberturas,id',
            'provincia_id' => 'nullable|exists:provincias,id',

            'iata_id' => 'nullable|exists:iata_codigos,id'
            


        ]);

        $ruta = RutaGeografica::find($validated['zona_ruta_geografica_id']);
        $tipoZonaId = optional($ruta)->tipo_zona_id;
        

        \App\Models\ComunaClasificacionOperativa::updateOrCreate(

            ['comuna_id' => $comuna_id],
            $validated + [
                'comuna_id' => $comuna_id,
                'tipo_zona_id' => $tipoZonaId, // <- se define automáticamente
            ]

        );

        $frecuencia = FrecuenciaDistribucion::updateOrCreate(
            [
                'comuna_id' => $comuna_id,
                'proveedor_id' => $validated['proveedor_id'],
            ]
        );

        // Guardar o actualizar el orden de transporte
        $orden = $request->input('orden_transporte');

        if ($orden !== null) {
            \App\Models\OrdenTransporte::updateOrCreate(
                [
                    'comuna_id' => $comuna_id,
                    'zona_ruta_geografica_id' => $validated['zona_ruta_geografica_id'],
                ],
                [
                    'orden' => $orden,
                ]
            );
        }



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

    private function aplicarFiltros($query, Request $request)
    {
        if ($regionId = $request->input('region')) {
            $query->where('region_id', $regionId);
        }

        if ($comunaNombre = $request->input('comuna')) {
            $query->where('Nombre', 'like', "%$comunaNombre%");
        }

        if ($proveedorNombre = $request->input('proveedor')) {
            $query->whereHas('clasificacionOperativa.proveedor', function ($q) use ($proveedorNombre) {
                $q->where('razon_social', 'like', "%$proveedorNombre%");
            });
        }

        if ($zonaId = $request->input('zona')) {
            $query->whereHas('clasificacionOperativa', function ($q) use ($zonaId) {
                $q->where('zona_id', $zonaId);
            });
        }

        if ($subzonaId = $request->input('subzona')) {
            $query->whereHas('clasificacionOperativa', function ($q) use ($subzonaId) {
                $q->where('subzona_id', $subzonaId);
            });
        }

        if ($coberturaId = $request->input('cobertura')) {
            $query->whereHas('clasificacionOperativa', function ($q) use ($coberturaId) {
                $q->where('cobertura_id', $coberturaId);
            });
        }

        return $query;
    }


    public function exportar(Request $request)
    {
        return Excel::download(
            new ClasificacionOperativaExport($request->all()),
            'clasificacion_operativa.xlsx'
        );
    }








}
