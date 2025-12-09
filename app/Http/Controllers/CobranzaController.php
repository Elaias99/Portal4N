<?php

namespace App\Http\Controllers;

use App\Models\Cobranza;
use Illuminate\Http\Request;
use App\Exports\CobranzasExport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class CobranzaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cobranza::query();

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where('razon_social', 'like', "%{$busqueda}%")
                ->orWhere('rut_cliente', 'like', "%{$busqueda}%");
        }

        $cobranzas = $query->orderBy('id', 'desc')->paginate(10);

        return view('cobranzas.index', compact('cobranzas'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('cobranzas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validated = $request->validate([
            'rut_cliente' =>  'required|string|max:255', 
            'razon_social' =>  'required|string|max:255',
            'servicio'    =>  'required|string|max:255',
            'creditos'    =>  'required|string|max:255',
        ]);

        $cobranza = Cobranza::create($validated);

        // ⚡ Si la solicitud viene vía AJAX, devolver JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'cobranza' => $cobranza
            ]);
        }

        return redirect()->route('cobranzas.index')
                        ->with('success', 'Cobranza creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cobranza $cobranza)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cobranza $cobranza)
    {
        //
        return view('cobranzas.edit', compact('cobranza'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cobranza $cobranza)
    {
        //
        $validated = $request->validate([

            'rut_cliente' =>  'required|string|max:255', 
            'razon_social' =>  'required|string|max:255',
            'servicio' =>  'required|string|max:255',
            'creditos' =>  'required|string|max:255',

        ]);

        $cobranza->update($validated);

        return redirect()->route('cobranzas.index')
                        ->with('success', 'Cobranza actualizada correctamente.');



    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cobranza $cobranza)
    {
        //
        $cobranza->delete();

        return redirect()->route('cobranzas.index')->with('success', 'Cobranza eliminado correctamente.');

    }

    public function export()
    {
        return Excel::download(new CobranzasExport, 'cobranzas.xlsx');
    }




    public function reprocesarPendientes(Request $request)
    {
        Log::info('📥 [reprocesarPendientes] Inicio del reprocesamiento');

        // 1️⃣ Buscar los pendientes en sesión
        $pendientes = session('sin_cobranza_pendientes') ?? session('sin_cobranza');

        if (empty($pendientes)) {
            Log::warning('⚠️ [reprocesarPendientes] No hay datos en la sesión sin_cobranza ni sin_cobranza_pendientes');
            return response()->json([
                'success' => false,
                'message' => 'No hay cobranzas pendientes para reprocesar.'
            ]);
        }

        Log::info('🧠 [reprocesarPendientes] Pendientes encontrados', ['total' => count($pendientes)]);

        $procesados = [];
        $omitidos = [];

        foreach ($pendientes as $item) {

            $rut = $item['rut_cliente'] ?? null;
            $folios = $item['folios'] ?? [];

            Log::info("📝 [reprocesarPendientes] FOLIOS RECIBIDOS PARA RUT {$rut}", [
                'folios' => $folios
            ]);

            // ⚠️ CORREGIDO → antes usabas $folios que siempre es truthy si es array
            if (!$rut || empty($folios)) {
                $omitidos[] = $rut ?: 'Sin RUT';
                Log::warning('⛔ [reprocesarPendientes] Faltan datos RUT o FOLIO', $item);
                continue;
            }

            Log::info("🔍 [reprocesarPendientes] Cantidad de folios para procesar", [
                'rut' => $rut,
                'cantidad_folios' => count($folios),
                'folios' => $folios
            ]);

            try {

                // 2️⃣ Buscar la cobranza creada
                $cobranza = \App\Models\Cobranza::where('rut_cliente', $rut)->first();

                if (!$cobranza) {
                    $omitidos[] = $rut;
                    Log::warning("❌ [reprocesarPendientes] No se encontró cobranza para el RUT {$rut}");
                    continue;
                }

                // 🔄 NUEVO: procesar cada folio individualmente
                foreach ($folios as $folio) {

                    Log::info("➡️ [reprocesarPendientes] Procesando folio {$folio} / RUT {$rut}");

                    // 3️⃣ Buscar documento financiero que quedó sin cobranza_id
                    $documento = \App\Models\DocumentoFinanciero::where('folio', $folio)
                        ->whereNull('cobranza_id')
                        ->first();

                    if ($documento) {

                        $fechaBase = $documento->fecha_docto ?? now();
                        $diasCredito = (int) ($cobranza->creditos ?? 0);

                        $documento->update([
                            'cobranza_id' => $cobranza->id,
                            'fecha_vencimiento' => Carbon::parse($fechaBase)
                                ->addDays($diasCredito)
                                ->format('Y-m-d'),
                            'updated_at' => now(),
                        ]);

                        // ⚠️ CORREGIDO → antes guardabas el array completo
                        $procesados[] = $folio;

                        Log::info("✅ [reprocesarPendientes] Documento actualizado correctamente", [
                            'folio' => $folio,
                            'cobranza_id' => $cobranza->id
                        ]);

                    } else {

                        $omitidos[] = $folio;

                        Log::warning("🔍 [reprocesarPendientes] No se encontró documento con folio {$folio} sin cobranza_id");
                    }
                }

            } catch (\Throwable $e) {

                Log::error("💥 [reprocesarPendientes] Error al procesar RUT {$rut}: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString()
                ]);

                $omitidos[] = $rut;
            }
        }

        // 4️⃣ Limpiar sesión
        session()->forget('sin_cobranza');
        session()->forget('sin_cobranza_pendientes');

        Log::info('🧹 [reprocesarPendientes] Sesión limpiada', [
            'procesados' => $procesados,
            'omitidos' => $omitidos,
        ]);

        // 5️⃣ Registrar movimiento
        try {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => null,
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'Reprocesamiento automático',
                'descripcion' => "Se reprocesaron " . count($procesados) . " documentos tras crear nuevas cobranzas.",
            ]);
            Log::info('📘 [reprocesarPendientes] MovimientoDocumento registrado');
        } catch (\Throwable $e) {
            Log::error('❗ [reprocesarPendientes] Error al registrar MovimientoDocumento: ' . $e->getMessage());
        }

        $success = count($procesados) > 0;

        Log::info('🏁 [reprocesarPendientes] Finalizado', [
            'success' => $success,
            'procesados' => $procesados,
            'omitidos' => $omitidos
        ]);

        return response()->json([
            'success' => $success,
            'message' => $success
                ? 'Reprocesamiento completado correctamente.'
                : 'No se pudo vincular ningún documento.',
            'procesados' => $procesados,
            'omitidos' => $omitidos,
        ]);
    }











    












}
