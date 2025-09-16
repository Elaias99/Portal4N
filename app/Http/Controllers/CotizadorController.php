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
        $cotizaciones = Cotizador::with('servicio')->get(); // carga también el servicio relacionado
        $servicios = Servicio::all(); // lista de servicios para el dropdown

        return view('cotizadores.index', compact('cotizaciones', 'servicios'));
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
        // Validaciones básicas
        $request->validate([
            'nombre_cliente' => 'required|string|max:255',
            'servicio_id'    => 'required|exists:servicios,id',
            'Origen'         => 'required|string|max:255',
            'Destino'        => 'required|string|max:255',
            'distancia_km'   => 'required|numeric|min:0',

            'origen_lat'     => 'required|numeric|between:-90,90',
            'origen_lon'     => 'required|numeric|between:-180,180',
            'destino_lat'    => 'required|numeric|between:-90,90',
            'destino_lon'    => 'required|numeric|between:-180,180',

            'estado'         => 'in:pendiente,aprobada,rechazada'
        ]);

        // Crear la cotización
        Cotizador::create($request->all());

        // Redirigir con mensaje de éxito
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
        $request->validate([
            'origen_lat'  => 'required|numeric|between:-90,90',
            'origen_lon'  => 'required|numeric|between:-180,180',
            'destino_lat' => 'required|numeric|between:-90,90',
            'destino_lon' => 'required|numeric|between:-180,180',
        ]);

        $coords = [
            [(float) $request->origen_lon, (float) $request->origen_lat],
            [(float) $request->destino_lon, (float) $request->destino_lat],
        ];

        // 👀 Log para verificar coordenadas usadas
        Log::info('Coordenadas enviadas a ORS', [
            'origen'  => $coords[0],
            'destino' => $coords[1],
        ]);

        $response = Http::withHeaders([
            'Authorization' => config('services.ors.key'),
        ])->post('https://api.openrouteservice.org/v2/directions/driving-car', [
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
