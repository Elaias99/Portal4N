<?php

namespace App\Http\Controllers;

use App\Exports\CobranzaCompraExport;
use App\Models\CobranzaCompra;
use App\Models\Banco;
use App\Models\TipoCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Models\DocumentoCompra;
use App\Models\MovimientoCompra;
use App\Services\ReferenciaNotasCompraService;

class CobranzaCompraController extends Controller
{
    /**
     * Mostrar listado de cobranzas de compras
     */
    public function index(Request $request)
    {
        $query = CobranzaCompra::query();

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where('razon_social', 'like', "%{$busqueda}%")
                  ->orWhere('rut_cliente', 'like', "%{$busqueda}%");
        }

        $cobranzasCompras  = $query->orderBy('id', 'ASC')->paginate(10);

        return view('cobranzas_compras.index', compact('cobranzasCompras'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $bancos = Banco::all();
        $tipoCuentas = TipoCuenta::all();

        return view('cobranzas_compras.create', compact('bancos', 'tipoCuentas'));
    }


    /**
     * Guardar nueva cobranza
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rut_cliente' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'servicio' => 'required|string|max:255',
            'creditos' => 'required|string|max:255',

            // Los nuevos integrados

            'tipo' => 'nullable|string|max:255',
            'facturacion' => 'nullable|string|max:255',
            'forma_pago' => 'nullable|string|max:255',
            'zona' => 'nullable|string|max:255',
            'importancia' => 'nullable|string|max:255',
            'responsable' => 'nullable|string|max:255',
            'nombre_cuenta' => 'nullable|string|max:255',
            'rut_cuenta' => 'nullable|string|max:255',
            'numero_cuenta' => 'nullable|string|max:255',
            'banco_id' => 'nullable|exists:bancos,id',
            'tipo_cuenta_id' => 'nullable|exists:tipo_cuentas,id',

            



        ]);

        $cobranza = CobranzaCompra::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'cobranza' => $cobranza
            ]);
        }

        return redirect()->route('cobranzas-compras.index')
                         ->with('success', 'Cobranza de compra creada correctamente.');
    }

    /**
     * Editar una cobranza existente
     */
    public function edit(CobranzaCompra $cobranzaCompra)
    {
        $bancos = Banco::all();
        $tipoCuentas = TipoCuenta::all();

        return view('cobranzas_compras.edit', compact('cobranzaCompra', 'bancos', 'tipoCuentas'));
    }


    /**
     * Actualizar una cobranza
     */
    public function update(Request $request, CobranzaCompra $cobranzaCompra)
    {
        $validated = $request->validate([
            'rut_cliente' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'servicio' => 'required|string|max:255',
            'creditos' => 'required|string|max:255',


            // Los nuevos integrados

            'tipo' => 'required|string|max:255',
            'facturacion' => 'required|string|max:255',
            'forma_pago' => 'required|string|max:255',
            'zona' => 'required|string|max:255',
            'importancia' => 'required|string|max:255',
            'responsable' => 'required|string|max:255',
            'nombre_cuenta' => 'required|string|max:255',
            'rut_cuenta' => 'required|string|max:255',
            'numero_cuenta' => 'required|string|max:255',
	        'banco_id' =>    'required|exists:bancos,id',
	        'tipo_cuenta_id' => 'required|exists:tipo_cuentas,id'

        ]);

        $cobranzaCompra->update($validated);

        return redirect()->route('cobranzas-compras.index')
                         ->with('success', 'Cobranza de compra actualizada correctamente.');
    }

    /**
     * Eliminar una cobranza
     */
    public function destroy(CobranzaCompra $cobranzaCompra)
    {
        $cobranzaCompra->delete();

        return redirect()->route('cobranzas-compras.index')
                         ->with('success', 'Cobranza de compra eliminada correctamente.');
    }

    /**
     * Reprocesar documentos de compras pendientes (sin cobranza_compra_id)
     */
    public function reprocesarPendientesCompras(Request $request)
    {
        Log::info('📥 [reprocesarPendientesCompras] Inicio del reprocesamiento');

        $pendientes = session('sin_compra_pendientes');

        if (!$pendientes || count($pendientes) === 0) {
            Log::warning('⚠️ No hay datos en la sesión sin_compra_pendientes');
            return response()->json([
                'success' => false,
                'message' => 'No hay documentos de compras pendientes para reprocesar.'
            ]);
        }

        $procesados = [];
        $omitidos = [];


        foreach ($pendientes as $item) {

            $rutProveedor = $item['rut_proveedor'] ?? null;

            if (!$rutProveedor) {
                continue;
            }

            Log::info("➡️ Reprocesando documentos del proveedor {$rutProveedor}");

            $cobranzaCompra = CobranzaCompra::where('rut_cliente', $rutProveedor)->first();

            if (!$cobranzaCompra) {
                Log::warning("⚠️ No se encontró cobranza_compra para el RUT {$rutProveedor}");
                continue;
            }

            // 🔥 CLAVE: traer TODOS los documentos pendientes del proveedor
            $documentos = DocumentoCompra::where('rut_proveedor', $rutProveedor)
                ->whereNull('cobranza_compra_id')
                ->get();

            if ($documentos->isEmpty()) {
                Log::warning("🔍 No hay documentos pendientes para el RUT {$rutProveedor}");
                continue;
            }

            foreach ($documentos as $documento) {

                $creditos = (int) ($cobranzaCompra->creditos ?? 0);

                $fechaVenc = Carbon::parse($documento->fecha_docto ?? now())
                    ->addDays($creditos)
                    ->format('Y-m-d');

                $status = Carbon::parse($fechaVenc)->isPast()
                    ? 'Vencido'
                    : 'Al día';

                $documento->update([
                    'cobranza_compra_id' => $cobranzaCompra->id,
                    'fecha_vencimiento'  => $fechaVenc,
                    'status_original'    => $status,
                    'estado'             => null,
                ]);


                // 🧪 DEBUG CLARO
                Log::info('🧪 [DEBUG REPROCESO] Documento reprocesado', [
                    'documento_id'      => $documento->id,
                    'folio'             => $documento->folio,
                    'tipo_documento_id' => $documento->tipo_documento_id,
                    'es_nota_credito'   => (int) $documento->tipo_documento_id === 61,
                    'rut_proveedor'     => $documento->rut_proveedor,
                ]);

                // 🧾 Movimiento por documento
                MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id'          => auth()->id(),
                    'tipo_movimiento'     => 'Reprocesamiento automático',
                    'descripcion'         => "Documento reprocesado tras creación de cobranza de compras.",
                    'datos_nuevos'        => [
                        'cobranza_compra_id' => $cobranzaCompra->id,
                        'fecha_vencimiento'  => $fechaVenc,
                        'creditos_aplicados' => $creditos,
                    ],
                    'fecha_cambio' => now(),
                ]);

                $procesados[] = $documento->folio;
            }
        }








        
        $service = new ReferenciaNotasCompraService();
        $sugerencias = [];

        $notasCredito = DocumentoCompra::whereIn('folio', $procesados)
            ->where('tipo_documento_id', 61)
            ->get();

        foreach ($notasCredito as $nota) {
            $resultado = $service->generarSugerencias($nota);

            if ($resultado['sugerida'] || ($resultado['alternativas'] && $resultado['alternativas']->count() > 0)) {
                $sugerencias[] = [
                    'nota' => $nota,
                    'sugerida' => $resultado['sugerida'],
                    'alternativas' => $resultado['alternativas'],
                ];
            }
        }

        // Guardar sugerencias en sesión para el modal
        if (!empty($sugerencias)) {
            session(['sugerencias_notas_compras' => $sugerencias]);
        }



        // 🧹 Limpiar sesión
        session()->forget('sin_compra_pendientes');

        Log::info('🧹 Sesión limpiada', [
            'procesados' => $procesados,
            'omitidos' => $omitidos
        ]);

        // Registrar movimiento general (con documento_compra_id = null)
        if (!empty($procesados)) {
            MovimientoCompra::create([
                'documento_compra_id' => null,
                'usuario_id' => auth()->id(),
                'tipo_movimiento' => 'Reprocesamiento global automático',
                'descripcion' => "Se reprocesaron " . count($procesados) . " documentos de compra tras crear nuevas cobranzas de compras.",
                'datos_nuevos' => [
                    'folios_reprocesados' => $procesados,
                ],
                'fecha_cambio' => now(),
            ]);
        }

        Log::info('🏁 Reprocesamiento finalizado', [
            'success' => true,
            'procesados' => $procesados,
            'omitidos' => $omitidos
        ]);

        return response()->json([
            'success' => true,
            'procesados' => $procesados,
            'omitidos' => $omitidos,
            'message' => 'Reprocesamiento de documentos de compra completado correctamente.'
        ]);
    }





    public function export(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $nombreArchivo = 'Cobranzas_Compras_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new CobranzaCompraExport($fechaInicio, $fechaFin),
            $nombreArchivo
        );
    }






}
