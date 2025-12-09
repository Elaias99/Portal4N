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
            $folio = $item['folio'] ?? null;
            $rutProveedor = $item['rut_proveedor'] ?? null;

            Log::info("➡️ Procesando folio {$folio} / RUT {$rutProveedor}");

            $cobranzaCompra = CobranzaCompra::where('rut_cliente', $rutProveedor)->first();

            if (!$cobranzaCompra) {
                Log::warning("⚠️ No se encontró cobranza_compra para el RUT {$rutProveedor}");
                $omitidos[] = $folio;
                continue;
            }

            $documento = DocumentoCompra::where('folio', $folio)
                ->whereNull('cobranza_compra_id')
                ->first();

            if ($documento) {
                $creditos = (int) ($cobranzaCompra->creditos ?? 0);

                $fechaVenc = $documento->fecha_vencimiento
                    ?? Carbon::parse($documento->fecha_docto ?? now())
                        ->addDays($creditos)
                        ->format('Y-m-d');

                $documento->update([
                    'cobranza_compra_id' => $cobranzaCompra->id,
                    'estado' => $documento->status_original ?? 'Pendiente',
                    'status_original' => $documento->status_original ?? 'Pendiente',
                    'fecha_vencimiento' => $fechaVenc,
                ]);

                // 🧾 Registrar trazabilidad por documento
                MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id' => auth()->id(),
                    'tipo_movimiento' => 'Reprocesamiento automático',
                    'descripcion' => "Se reprocesó el documento folio {$folio} tras la creación de una nueva cobranza de compras.",
                    'datos_nuevos' => [
                        'cobranza_compra_id' => $cobranzaCompra->id,
                        'fecha_vencimiento' => $fechaVenc,
                        'creditos_aplicados' => $creditos,
                    ],
                    'fecha_cambio' => now(),
                ]);

                $procesados[] = $folio;

                Log::info("✅ DocumentoCompra actualizado y movimiento registrado", [
                    'folio' => $folio,
                    'cobranza_compra_id' => $cobranzaCompra->id
                ]);
            } else {
                $omitidos[] = $folio;
                Log::warning("🔍 No se encontró documento con folio {$folio} sin cobranza_compra_id");
            }
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
