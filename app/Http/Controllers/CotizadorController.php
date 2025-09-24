<?php

namespace App\Http\Controllers;

use App\Models\Cotizador;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CotizadorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cotizaciones = Cotizador::with([
            'servicio',
            'transporte',
            'maquilado.insumos',
            'maquilado.tipoMaquila',
            'cargasTransporte' 
        ])->get();

        

        $servicios = Servicio::all();
        $transportes = \App\Models\Transporte::all();
        $tiposMaquila = \App\Models\TipoMaquilado::all(); 

        return view('cotizadores.index', compact('cotizaciones', 'servicios', 'transportes', 'tiposMaquila'));
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validaciones comunes
        $request->validate([
            'nombre_cliente' => 'required|string|max:255',
            'servicio_id'    => 'required|exists:servicios,id',
            'estado'         => 'in:pendiente,aprobada,rechazada',
        ]);

        $data = [
            'nombre_cliente' => $request->nombre_cliente,
            'servicio_id'    => $request->servicio_id,
            'estado'         => $request->estado,
        ];

        // Si el servicio es Transporte
        if ((int) $request->servicio_id === 1) {
            $request->validate([
                'transporte_id'     => 'required|exists:transportes,id',
                'Origen'            => 'required|string|max:255',
                'Destino'           => 'required|string|max:255',
                'distancia_km'      => 'required|numeric|min:0',
                'origen_lat'        => 'required|numeric|between:-90,90',
                'origen_lon'        => 'required|numeric|between:-180,180',
                'destino_lat'       => 'required|numeric|between:-90,90',
                'destino_lon'       => 'required|numeric|between:-180,180',
                'lleva_pioneta'     => 'required|boolean',
                'cantidad_pionetas' => 'nullable|integer|min:0',
                'jornada_pioneta'   => 'nullable|string|max:50',
                'con_carga'         => 'required|boolean',
            ]);

            $data = array_merge($data, $request->only([
                'transporte_id',
                'Origen', 'Destino',
                'origen_lat', 'origen_lon',
                'destino_lat', 'destino_lon',
                'distancia_km',
                'lleva_pioneta',
                'cantidad_pionetas',
                'jornada_pioneta',
                'con_carga',
            ]));
        }

        // Crear primero el Cotizador
        $cotizador = Cotizador::create($data);

        // Si el servicio es Transporte y tiene carga → crear registros de cargas
        if ((int) $request->servicio_id === 1 && $request->con_carga == 1) {
            $request->validate([
                'cargas.*.descripcion' => 'required|string|max:255',
                'cargas.*.cantidad'    => 'required|integer|min:1',
                'cargas.*.medida'      => 'nullable|string|max:50',
                'cargas.*.peso_total'  => 'nullable|numeric|min:0',
                'cargas.*.unidad_peso' => 'nullable|string|max:20',
            ]);

            foreach ($request->cargas as $carga) {
                $cotizador->cargasTransporte()->create([
                    'descripcion' => $carga['descripcion'],
                    'cantidad'    => $carga['cantidad'],
                    'medida'      => $carga['medida'] ?? null,
                    'peso_total'  => $carga['peso_total'] ?? null,
                    'unidad_peso' => $carga['unidad_peso'] ?? null,
                ]);
            }
        }


        // Si el servicio es Maquila → crear registro en la nueva tabla
        if ((int) $request->servicio_id === 2) {
            $request->validate([
                'insumo'              => 'required|in:proveedor,cliente',
                'tipo_maquila_id'     => 'required|exists:tipos_maquila,id',
                'duracion_proceso'    => 'nullable|string|max:100',
                'requiere_transporte' => 'nullable|boolean',

                // validaciones para arrays de insumos cuando insumo = proveedor
                'insumos'           => 'required_if:insumo,proveedor|array',
                'insumos.*.detalle' => 'required_if:insumo,proveedor|string|max:255',
                'insumos.*.cantidad'=> 'required_if:insumo,proveedor|integer|min:1',
                'insumos.*.precio'  => 'required_if:insumo,proveedor|numeric|min:0',


                // validaciones adicionales si requiere transporte
                'transporte_id'     => 'required_if:requiere_transporte,1|exists:transportes,id',
                'Origen'            => 'required_if:requiere_transporte,1|string|max:255',
                'Destino'           => 'required_if:requiere_transporte,1|string|max:255',
                'distancia_km'      => 'nullable|numeric|min:0',
                'origen_lat'        => 'nullable|numeric|between:-90,90',
                'origen_lon'        => 'nullable|numeric|between:-180,180',
                'destino_lat'       => 'nullable|numeric|between:-90,90',
                'destino_lon'       => 'nullable|numeric|between:-180,180',
                'lleva_pioneta'     => 'nullable|boolean',
                'cantidad_pionetas' => 'nullable|integer|min:0',
                'jornada_pioneta'   => 'nullable|string|max:100',
                'con_carga'         => 'nullable|boolean',
            ]);

            // Crear maquilado
            $maquilado = $cotizador->maquilado()->create([
                'insumo'              => $request->insumo,
                'tipo_maquila_id'     => $request->tipo_maquila_id,
                'duracion_proceso'    => $request->duracion_proceso,
                'requiere_transporte' => $request->requiere_transporte ?? 0,
            ]);

            // Guardar insumos (si aplica)
            if ($request->insumo === 'proveedor' && $request->has('insumos')) {
                foreach ($request->insumos as $insumo) {
                    $maquilado->insumos()->create([
                        'detalle'  => $insumo['detalle'],
                        'cantidad' => $insumo['cantidad'],
                        'precio'   => $insumo['precio'],
                        'subtotal' => $insumo['cantidad'] * $insumo['precio'],
                    ]);
                }
            }


            // Guardar transporte si requiere
            if ($request->requiere_transporte == 1) {
                $maquilado->transporte()->create([
                    'transporte_id'     => $request->transporte_id,
                    'origen'            => $request->Origen,
                    'origen_lat'        => $request->origen_lat,
                    'origen_lon'        => $request->origen_lon,
                    'destino'           => $request->Destino,
                    'destino_lat'       => $request->destino_lat,
                    'destino_lon'       => $request->destino_lon,
                    'distancia_km'      => $request->distancia_km,
                    'lleva_pioneta'     => $request->lleva_pioneta ?? 0,
                    'cantidad_pionetas' => $request->cantidad_pionetas,
                    'jornada_pioneta'   => $request->jornada_pioneta,
                    'con_carga'         => $request->con_carga ?? 0,
                ]);
            }
        }



        return redirect()->route('cotizadores.index')
            ->with('success', 'Cotización creada correctamente.');
    }






    /**
     * Display the specified resource.
     */
    public function show(Cotizador $cotizador)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cotizador $cotizador)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cotizador $cotizador)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cotizador $cotizador)
    {
        //
    }

    

    public function calcularDistancia(Request $request)
    {
        // Si en algún caso llaman este método sin ser transporte, evitamos el cálculo
        if ($request->input('perfil') === null) {
            return response()->json([
                'error' => 'El cálculo de distancia solo aplica para Transporte.'
            ], 400);
        }

        $request->validate([
            'origen_lat'  => 'required|numeric|between:-90,90',
            'origen_lon'  => 'required|numeric|between:-180,180',
            'destino_lat' => 'required|numeric|between:-90,90',
            'destino_lon' => 'required|numeric|between:-90,90',
            'perfil'      => 'required|string|in:driving-car,driving-hgv,cycling-regular,foot-walking'
        ]);

        $coords = [
            [(float) $request->origen_lon, (float) $request->origen_lat],
            [(float) $request->destino_lon, (float) $request->destino_lat],
        ];

        $perfil = $request->perfil;

        Log::info('Coordenadas enviadas a ORS', [
            'origen'  => $coords[0],
            'destino' => $coords[1],
            'perfil'  => $perfil,
        ]);

        $response = Http::withHeaders([
            'Authorization' => config('services.ors.key'),
        ])->post("https://api.openrouteservice.org/v2/directions/{$perfil}", [
            'coordinates' => $coords,
        ]);

        Log::info('Respuesta ORS:', $response->json());

        if ($response->successful()) {
            return response()->json(
                $this->procesarRespuestaORS($response->json())
            );
        }

        return response()->json([
            'error'   => 'No se pudo calcular la distancia con ORS.',
            'detalle' => $response->body()
        ], 500);
    }






    /**
     * Procesa la respuesta de OpenRouteService y devuelve
     * distancia, duración y pasos de la ruta.
     */
    private function procesarRespuestaORS(array $data): array
    {
        if (!isset($data['routes'][0]['segments'][0])) {
            return [
                'error' => 'Respuesta inesperada de ORS',
                'raw'   => $data,
            ];
        }

        $segmento = $data['routes'][0]['segments'][0];

        $distanciaKm = $segmento['distance'] / 1000; // metros → km
        $duracionMin = $segmento['duration'] / 60;  // segundos → minutos

        $instrucciones = collect($segmento['steps'])->map(function ($step) {
            return $step['instruction'];
        })->toArray();

        return [
            'distancia_km' => round($distanciaKm, 2),
            'duracion_min' => round($duracionMin, 1),
            'instrucciones' => $instrucciones
        ];
    }

    public function geocodificar(Request $request)
    {
        $request->validate([
            'direccion' => 'required|string|min:3|max:255',
        ]);

        $resp = Http::withHeaders([
            'Authorization' => config('services.ors.key'),
        ])->get('https://api.openrouteservice.org/geocode/search', [
            'text' => $request->input('direccion'),
            'boundary.country' => 'CL',
            'size' => 1,
           
        ]);

        if (!$resp->successful()) {
            Log::warning('Geocodificación ORS fallida', [
                'status' => $resp->status(),
                'body'   => $resp->body()
            ]);
            return response()->json(['error' => 'No se pudo geocodificar la dirección.'], 422);
        }

        $json = $resp->json();

        if (empty($json['features']) || empty($json['features'][0]['geometry']['coordinates'])) {
            return response()->json(['error' => 'Dirección no encontrada.'], 404);
        }

        [$lon, $lat] = $json['features'][0]['geometry']['coordinates'];
        $props = $json['features'][0]['properties'];

        $label = $props['label'] ?? $request->input('direccion');


        return response()->json([
            'lat'   => round($lat, 6),
            'lon'   => round($lon, 6),
            'label' => $label,
        ]);
    }



    
































}
