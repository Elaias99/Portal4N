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
use Illuminate\Support\Facades\Auth;

class CobranzaCompraController extends Controller
{
    /**
     * Mostrar listado de cobranzas de compras
     */
    public function index(Request $request)
    {
        $query = CobranzaCompra::query()
            ->withCount([
                'documentos as documentos_rcv_compras_count',
                'honorariosMensualesRec as documentos_bh_count',
        ]);

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

            'tipo' => 'nullable|string|max:255',
            'facturacion' => 'nullable|string|max:255',
            'forma_pago' => 'nullable|string|max:255',
            'zona' => 'nullable|string|max:255',
            'importancia' => 'nullable|string|max:255',
            'responsable' => 'nullable|string|max:255',
            'nombre_cuenta' => 'nullable|string|max:255',
            'rut_cuenta' => 'nullable|string|max:255',
            'numero_cuenta' => 'nullable|string|max:255',

            'banco_id' => 'nullable|string|max:255',
            'banco_otro' => 'nullable|string|max:255',

            'tipo_cuenta_id' => 'nullable|string|max:255',
            'tipo_cuenta_otro' => 'nullable|string|max:255',
        ]);

        $bancoSeleccionado = null;
        $tipoCuentaSeleccionada = null;

        /*
        |--------------------------------------------------------------------------
        | Resolver banco
        |--------------------------------------------------------------------------
        */
        $bancoId = $validated['banco_id'] ?? null;
        $bancoOtro = trim((string) ($validated['banco_otro'] ?? ''));

        if ($bancoId === '__otro__') {
            if ($bancoOtro === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'banco_otro' => 'Debe ingresar el nombre del banco.',
                ]);
            }

            $nombreBanco = preg_replace('/\s+/', ' ', $bancoOtro);

            $bancoSeleccionado = Banco::whereRaw('LOWER(TRIM(nombre)) = ?', [
                mb_strtolower($nombreBanco)
            ])->first();

            if (!$bancoSeleccionado) {
                $bancoSeleccionado = Banco::create([
                    'nombre' => $nombreBanco,
                ]);
            }

            $validated['banco_id'] = $bancoSeleccionado->id;
        } elseif ($bancoId) {
            $bancoSeleccionado = Banco::find($bancoId);

            if (!$bancoSeleccionado) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'banco_id' => 'El banco seleccionado no es válido.',
                ]);
            }

            $validated['banco_id'] = (int) $bancoId;
        } else {
            $validated['banco_id'] = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolver tipo de cuenta
        |--------------------------------------------------------------------------
        */
        $tipoCuentaId = $validated['tipo_cuenta_id'] ?? null;
        $tipoCuentaOtro = trim((string) ($validated['tipo_cuenta_otro'] ?? ''));

        if ($tipoCuentaId === '__otro__') {
            if ($tipoCuentaOtro === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'tipo_cuenta_otro' => 'Debe ingresar el tipo de cuenta.',
                ]);
            }

            $nombreTipoCuenta = preg_replace('/\s+/', ' ', $tipoCuentaOtro);

            $tipoCuentaSeleccionada = TipoCuenta::whereRaw('LOWER(TRIM(nombre)) = ?', [
                mb_strtolower($nombreTipoCuenta)
            ])->first();

            if (!$tipoCuentaSeleccionada) {
                $tipoCuentaSeleccionada = TipoCuenta::create([
                    'nombre' => $nombreTipoCuenta,
                ]);
            }

            $validated['tipo_cuenta_id'] = $tipoCuentaSeleccionada->id;
        } elseif ($tipoCuentaId) {
            $tipoCuentaSeleccionada = TipoCuenta::find($tipoCuentaId);

            if (!$tipoCuentaSeleccionada) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'tipo_cuenta_id' => 'El tipo de cuenta seleccionado no es válido.',
                ]);
            }

            $validated['tipo_cuenta_id'] = (int) $tipoCuentaId;
        } else {
            $validated['tipo_cuenta_id'] = null;
        }

        unset($validated['banco_otro'], $validated['tipo_cuenta_otro']);

        $cobranza = CobranzaCompra::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'cobranza' => $cobranza,

                'banco' => $bancoSeleccionado ? [
                    'id' => $bancoSeleccionado->id,
                    'nombre' => $bancoSeleccionado->nombre,
                ] : null,

                'tipo_cuenta' => $tipoCuentaSeleccionada ? [
                    'id' => $tipoCuentaSeleccionada->id,
                    'nombre' => $tipoCuentaSeleccionada->nombre,
                ] : null,
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
        $pendientes = session('sin_compra_pendientes');

        if (!$pendientes || count($pendientes) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No hay documentos de compras pendientes para reprocesar.'
            ]);
        }

        $procesados = [];
        $procesadosIds = [];
        $omitidos = [];

        foreach ($pendientes as $item) {

            $rutProveedor = $item['rut_proveedor'] ?? null;

            if (!$rutProveedor) {
                $omitidos[] = [
                    'motivo' => 'RUT proveedor vacío',
                    'item' => $item,
                ];

                continue;
            }

            $cobranzaCompra = CobranzaCompra::where('rut_cliente', $rutProveedor)->first();

            if (!$cobranzaCompra) {
                $omitidos[] = [
                    'rut_proveedor' => $rutProveedor,
                    'motivo' => 'No existe cobranza_compra asociada',
                ];

                continue;
            }

            $documentos = DocumentoCompra::where('rut_proveedor', $rutProveedor)
                ->whereNull('cobranza_compra_id')
                ->get();

            if ($documentos->isEmpty()) {
                $omitidos[] = [
                    'rut_proveedor' => $rutProveedor,
                    'motivo' => 'No hay documentos pendientes sin cobranza_compra_id',
                ];

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

                $formaPagoNormalizada = mb_strtoupper(
                    trim((string) ($cobranzaCompra->forma_pago ?? ''))
                );

                $esPagoAutomatico = in_array($formaPagoNormalizada, [
                    'CAJA CHICA',
                    'FONDO POR RENDIR',
                ], true);

                $datosActualizar = [
                    'cobranza_compra_id' => $cobranzaCompra->id,
                    'fecha_vencimiento'  => $fechaVenc,
                    'status_original'    => $status,
                ];

                if ($esPagoAutomatico) {
                    $datosActualizar['estado'] = 'Pago';
                    $datosActualizar['fecha_estado_manual'] = now();
                    $datosActualizar['saldo_pendiente'] = 0;
                } else {
                    $datosActualizar['estado'] = null;
                }

                $documento->update($datosActualizar);

                if ($esPagoAutomatico && !$documento->pagos()->exists()) {
                    $documento->pagos()->create([
                        'fecha_pago' => Carbon::parse($documento->fecha_docto ?? now())->format('Y-m-d'),
                        'user_id'    => Auth::id(),
                        'origen'     => 'forma_pago_automatica',
                    ]);
                }

                MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id'          => Auth::id(),
                    'tipo_movimiento'     => 'Reprocesamiento automático',
                    'descripcion'         => $esPagoAutomatico
                        ? 'Documento reprocesado tras creación de cobranza de compras y cerrado automáticamente por forma de pago.'
                        : 'Documento reprocesado tras creación de cobranza de compras.',
                    'datos_nuevos'        => [
                        'cobranza_compra_id' => $cobranzaCompra->id,
                        'fecha_vencimiento'  => $fechaVenc,
                        'creditos_aplicados' => $creditos,
                        'forma_pago'         => $cobranzaCompra->forma_pago,
                        'pago_automatico'    => $esPagoAutomatico,
                        'estado'             => $esPagoAutomatico ? 'Pago' : null,
                        'saldo_pendiente'    => $esPagoAutomatico ? 0 : $documento->saldo_pendiente,
                    ],
                    'fecha_cambio' => now(),
                ]);

                $procesados[] = $documento->folio;
                $procesadosIds[] = $documento->id;
            }
        }

        $service = new ReferenciaNotasCompraService();
        $sugerencias = [];

        $notasCredito = DocumentoCompra::whereIn('id', $procesadosIds)
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

        $sugerenciasExistentes = session('sugerencias_notas_compras', []);

        $sugerenciasFinales = collect($sugerenciasExistentes)
            ->merge($sugerencias)
            ->filter(function ($item) {
                return isset($item['nota']) && $item['nota'];
            })
            ->unique(function ($item) {
                return $item['nota']->id ?? null;
            })
            ->values()
            ->all();

        if (!empty($sugerenciasFinales)) {
            session(['sugerencias_notas_compras' => $sugerenciasFinales]);
        }

        session()->forget('sin_compra_pendientes');

        if (!empty($procesados)) {
            MovimientoCompra::create([
                'documento_compra_id' => null,
                'usuario_id' => Auth::id(),
                'tipo_movimiento' => 'Reprocesamiento global automático',
                'descripcion' => 'Se reprocesaron ' . count($procesados) . ' documentos de compra tras crear nuevas cobranzas de compras.',
                'datos_nuevos' => [
                    'folios_reprocesados' => $procesados,
                    'documentos_reprocesados_ids' => $procesadosIds,
                ],
                'fecha_cambio' => now(),
            ]);
        }

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




    /**
     * Vista de Salud de Proveedores
     */
    public function salud()
    {
        $cobranzasCompras = CobranzaCompra::with(['banco', 'tipoCuenta'])
            ->orderBy('razon_social')
            ->paginate(20);

        $evaluados = $cobranzasCompras->getCollection()->map(function ($p) {

            $problemas = [];
            $nivel = 'saludable'; // saludable | advertencia | critico

            // =========================
            // VALIDACIONES CRÍTICAS
            // =========================

            if (empty($p->servicio) || in_array(strtoupper(trim($p->servicio)), ['NULL', 'NO', 'SIN INFO', '1'])) {
                $problemas[] = 'Servicio inválido o no definido';
                $nivel = 'critico';
            }

            if ($p->creditos === null || !is_numeric($p->creditos)) {
                $problemas[] = 'Créditos no definidos';
                $nivel = 'critico';
            }

            if (empty($p->banco_id) || empty($p->tipo_cuenta_id)) {
                $problemas[] = 'Información bancaria incompleta';
                $nivel = 'critico';
            }

            if (empty($p->numero_cuenta) || empty($p->rut_cuenta)) {
                $problemas[] = 'Datos de cuenta incompletos';
                $nivel = 'critico';
            }

            // =========================
            // VALIDACIONES DE ADVERTENCIA
            // =========================

            if ($nivel !== 'critico') {

                if (empty($p->responsable)) {
                    $problemas[] = 'Responsable no asignado';
                    $nivel = 'advertencia';
                }

                if (empty($p->importancia)) {
                    $problemas[] = 'Importancia no definida';
                    $nivel = 'advertencia';
                }

                if (empty($p->zona)) {
                    $problemas[] = 'Zona no definida';
                    $nivel = 'advertencia';
                }
            }

            return [
                'proveedor' => $p,
                'problemas' => $problemas,
                'nivel' => $nivel,
            ];
        });

        // Mantiene la paginación pero con items evaluados
        $cobranzasCompras->setCollection($evaluados);

        return view('cobranzas_compras.salud', [
            'cobranzasCompras' => $cobranzasCompras,
            'evaluados' => $evaluados,
        ]);
    }




    public function cancelarPendientesCompras(Request $request)
    {
        session()->forget([
            'sin_compra_pendientes',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Flujo guiado de compras cancelado correctamente.'
        ]);
    }






}
