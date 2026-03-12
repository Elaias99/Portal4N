<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimientoDocumento;
use App\Models\MovimientoCompra;
use App\Models\DocumentoCompra;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use App\Exports\PagosMasivosDocumentoCompraExport;
use Illuminate\Support\Str;


class PagoDocumentoController extends Controller
{
    //

    public function store(Request $request, $id)
    {
        $request->validate([
            'fecha_pago' => 'required|date|before_or_equal:today',
        ], [
            'fecha_pago.before_or_equal' => 'La fecha del pago no debe sobrepasar la fecha actual.',
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
        ]);

        $tipo = $request->input('tipo', 'ventas');
        $esCompra = $tipo === 'compra';

        $documento = $esCompra
            ? \App\Models\DocumentoCompra::findOrFail($id)
            : \App\Models\DocumentoFinanciero::findOrFail($id);

        // Evitar duplicados
        if ($documento->pagos()->exists()) {
            return back()->withErrors(['fecha_pago' => 'Este documento ya tiene un pago registrado.']);
        }

        $estadoAnterior = $documento->estado ?? $documento->status;
        $saldoAnterior = $documento->saldo_pendiente;

        // Crear el pago
        $documento->pagos()->create([
            'fecha_pago' => $request->fecha_pago,
            'user_id' => Auth::id(),
            'origen' => 'manual',
        ]);
        


        // Actualizar estado y saldo
        $campoEstado = $esCompra ? 'estado' : 'status';
        $documento->update([
            $campoEstado => 'Pago',
            'fecha_estado_manual' => now(),
            'saldo_pendiente' => 0,
        ]);

        // Registrar movimiento
        if ($esCompra) {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => 'Pago',
                'fecha_cambio' => now(),
                'tipo_movimiento' => 'Registro de pago',
                'descripcion' => "Se registró un pago el {$request->fecha_pago}.",
                'datos_anteriores' => [
                    'estado' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ],
                'datos_nuevos' => [
                    'fecha_pago' => $request->fecha_pago,
                    'saldo_actual' => 0,
                ],
            ]);
        } else {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Pago registrado',
                'descripcion' => "Se registró un pago el {$request->fecha_pago}.",
                'datos_nuevos' => [
                    'fecha_pago' => $request->fecha_pago,
                    'saldo_actual' => 0,
                ],
            ]);
        }

        return back()->with('success', 'Pago registrado correctamente y estado actualizado.');
    }



    public function destroy($id)
    {
        $pago = \App\Models\Pago::findOrFail($id);
        $documento = $pago->documentoFinanciero ?? $pago->documentoCompra;

        $tipoDocumento = $documento instanceof \App\Models\DocumentoCompra ? 'compra' : 'financiero';

        // Bloquear eliminación manual de pagos generados por referencia
        if ($pago->origen === 'referencia_nc') {
            $route = $tipoDocumento === 'compra' ? 'finanzas_compras.show' : 'documentos.detalles';

            return redirect()
                ->route($route, $documento->id)
                ->with('warning', 'Este pago fue generado automáticamente por una referencia. Para revertirlo, quite la referencia asociada.');
        }

        $estadoAnterior = $documento->estado ?? $documento->status;
        $saldoAnterior = $documento->saldo_pendiente;

        $datosAnteriores = [
            'fecha_pago' => $pago->fecha_pago,
            'user_id' => $pago->user_id,
            'origen' => $pago->origen,
        ];

        // Eliminar pago
        $pago->delete();

        // Recalcular saldo
        if (method_exists($documento, 'recalcularSaldoPendiente')) {
            $documento->recalcularSaldoPendiente();
        }

        // Determinar nuevo estado
        if ($documento->pagos()->count() === 0) {
            $nuevoEstado = now()->gt(\Carbon\Carbon::parse($documento->fecha_vencimiento))
                ? 'Vencido'
                : 'Al día';

            $campoEstado = $tipoDocumento === 'compra' ? 'estado' : 'status';
            $documento->update([
                $campoEstado => null,
                'status_original' => $nuevoEstado,
                'fecha_estado_manual' => null,
            ]);
        } else {
            $nuevoEstado = 'Pago';
        }

        // Registrar movimiento
        if ($tipoDocumento === 'financiero') {
            \App\Models\MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Eliminación de pago',
                'descripcion' => "Se eliminó un pago registrado el {$datosAnteriores['fecha_pago']} correspondiente al documento folio {$documento->folio}.",
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => [
                    'nuevo_estado' => $nuevoEstado,
                    'saldo_actual' => $documento->saldo_pendiente,
                ],
            ]);
        } else {
            \App\Models\MovimientoCompra::create([
                'documento_compra_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado,
                'fecha_cambio' => now(),
                'tipo_movimiento' => 'Eliminación de pago',
                'descripcion' => "Se eliminó un pago registrado el {$datosAnteriores['fecha_pago']} correspondiente al documento de compra folio {$documento->folio}.",
                'datos_anteriores' => array_merge($datosAnteriores, [
                    'estado_anterior' => $estadoAnterior,
                    'saldo_anterior' => $saldoAnterior,
                ]),
                'datos_nuevos' => [
                    'nuevo_estado' => $nuevoEstado,
                    'saldo_actual' => $documento->saldo_pendiente,
                ],
            ]);
        }

        // Redirección
        $route = $tipoDocumento === 'compra' ? 'finanzas_compras.show' : 'documentos.detalles';
        return redirect()
            ->route($route, $documento->id)
            ->with('success', 'Pago eliminado, movimiento registrado y estado actualizado correctamente.');
    }











    public function storeMasivo(Request $request)
    {

        $request->validate([
            'fecha_pago'      => 'required|date|before_or_equal:today',
            'documentos'      => 'required|array|min:1',
            'documentos.*'    => 'integer|exists:documentos_compras,id',
            'operaciones'     => 'required|array|min:1',
            'operaciones.*'   => 'required|in:pago,abono',
            'montos'          => 'nullable|array',
            'montos.*'        => 'integer|min:1',
        ]);

        $ids       = $request->documentos;
        $fechaPago = $request->fecha_pago;
        $montos    = $request->montos ?? [];

        $procesados = 0;
        $duplicados = 0;
        $errores    = [];

        /**
         * EXPORT AGRUPADO POR EMPRESA
         * [
         *   empresa_id => [
         *       'empresa' => 'PMCB',
         *       'items'   => [...]
         *   ]
         * ]
         */
        $exportPorEmpresa = [];

        foreach ($ids as $id) {

            $documento = \App\Models\DocumentoCompra::with('empresa')->find($id);

            if (!$documento) {
                $duplicados++;
                continue;
            }

            if ($documento->pagos()->exists()) {
                $duplicados++;
                continue;
            }

            $operacion = $request->operaciones[$id] ?? null;
            if (!$operacion) {
                $errores[] = [
                    'documento_id' => $id,
                    'error'        => 'Operación no definida',
                ];
                continue;
            }

            $estadoAnterior = $documento->estado;
            $saldoAnterior  = $documento->saldo_pendiente;

            // =========================
            // PAGO TOTAL
            // =========================
            if ($operacion === 'pago') {

                $documento->pagos()->create([
                    'fecha_pago' => $fechaPago,
                    'user_id'    => Auth::id(),
                ]);

                $documento->update([
                    'estado'              => 'Pago',
                    'fecha_estado_manual' => now(),
                    'saldo_pendiente'     => 0,
                ]);

                \App\Models\MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id'          => Auth::id(),
                    'estado_anterior'     => $estadoAnterior,
                    'nuevo_estado'        => 'Pago',
                    'fecha_cambio'        => now(),
                    'tipo_movimiento'     => 'Pago registrado (masivo)',
                    'descripcion'         => "Pago masivo registrado el {$fechaPago}.",
                    'datos_anteriores'    => [
                        'estado'         => $estadoAnterior,
                        'saldo_anterior' => $saldoAnterior,
                    ],
                    'datos_nuevos' => [
                        'fecha_pago'   => $fechaPago,
                        'saldo_actual' => 0,
                    ],
                ]);

                $empresaId = $documento->empresa_id;

                $exportPorEmpresa[$empresaId]['empresa'] ??=
                    optional($documento->empresa)->Nombre ?? 'Empresa';


                $exportPorEmpresa[$empresaId]['items'][] = [
                    'documento_id' => $documento->id,
                    'tipo'         => 'pago',
                    'monto'        => $documento->monto_total,
                    'fecha'        => $fechaPago,
                ];

                $procesados++;
                continue;
            }

            // =========================
            // ABONO
            // =========================
            if ($operacion === 'abono') {

                $monto = (int) ($montos[$id] ?? 0);

                if ($monto <= 0 || $monto > $saldoAnterior) {
                    $errores[] = [
                        'documento_id' => $id,
                        'error'        => 'Monto de abono inválido',
                    ];
                    continue;
                }

                $documento->abonos()->create([
                    'monto'       => $monto,
                    'fecha_abono' => $fechaPago,
                ]);

                $documento->recalcularSaldoPendiente();

                $documento->update([
                    'estado'              => 'Abono',
                    'fecha_estado_manual' => now(),
                ]);

                \App\Models\MovimientoCompra::create([
                    'documento_compra_id' => $documento->id,
                    'usuario_id'          => Auth::id(),
                    'estado_anterior'     => $estadoAnterior,
                    'nuevo_estado'        => 'Abono',
                    'fecha_cambio'        => now(),
                    'tipo_movimiento'     => 'Registro de abono (masivo)',
                    'descripcion'         => "Abono masivo de {$monto} registrado el {$fechaPago}.",
                    'datos_anteriores'    => [
                        'estado'         => $estadoAnterior,
                        'saldo_anterior' => $saldoAnterior,
                    ],
                    'datos_nuevos' => [
                        'monto'       => $monto,
                        'nuevo_saldo' => $documento->saldo_pendiente,
                    ],
                ]);

                $empresaId = $documento->empresa_id;


                $exportPorEmpresa[$empresaId]['empresa'] ??=
                    optional($documento->empresa)->Nombre ?? 'Empresa';



                $exportPorEmpresa[$empresaId]['items'][] = [
                    'documento_id' => $documento->id,
                    'tipo'         => 'abono',
                    'monto'        => $monto,
                    'fecha'        => $fechaPago,
                ];

                $procesados++;
            }
        }

        /**
         *Generar tokens por empresa
         */
        $downloads = [];

        foreach ($exportPorEmpresa as $empresaId => $data) {

            $token = (string) Str::uuid();

            Cache::put(
                "pagos_masivos_empresa:{$token}",
                $data,
                now()->addMinutes(10)
            );

            $downloads[] = [
                'empresa' => $data['empresa'],
                'url'     => route('documentos.pagos.masivo.empresa.descargar', $token),
            ];
        }

        return response()->json([
            'ok'          => true,
            'procesados'  => $procesados,
            'duplicados'  => $duplicados,
            'errores'     => $errores,
            'downloads'   => $downloads,
        ]);
    }






    public function exportPagosMasivos()
    {


        $ids = session('pagos_masivos_export');



        if (!$ids || !is_array($ids) || count($ids) === 0) {



            abort(404, 'No hay pagos masivos para exportar.');
        }



        // Limpiar sesión para evitar descargas duplicadas
        session()->forget('pagos_masivos_export');


        $nombreArchivo = 'pagos_masivos_' . now()->format('Ymd_His') . '.xlsx';



        return Excel::download(
            new \App\Exports\PagosMasivosDocumentoCompraExport($ids),
            $nombreArchivo
        );
    }



    public function downloadPagosMasivosEmpresa(string $token)
    {
        $cacheKey = "pagos_masivos_empresa:{$token}";

        $data = Cache::get($cacheKey);

        

        if (!$data || empty($data['items'])) {
            abort(404, 'No hay pagos masivos para exportar.');
        }

        Cache::forget($cacheKey);

        $empresa   = str_replace(' ', '_', $data['empresa']);
        $fecha     = now()->format('Ymd_His');
        $nombre    = "{$empresa}_Pago_Proveedores_{$fecha}.xlsx";

        return Excel::download(
            new \App\Exports\PagosMasivosDocumentoCompraExport($data['items']),
            $nombre
        );
    }











    public function buscarDocumentos(Request $request)
    {
        $filtro = trim($request->get('filtro'));

        $documentos = DocumentoCompra::where(function ($query) use ($filtro) {

                if (ctype_digit($filtro)) {
                    // Búsqueda EXACTA por folio
                    $query->where('folio', $filtro);
                } else {
                    // Búsqueda por texto
                    $query->where('razon_social', 'like', "%{$filtro}%")
                        ->orWhere('rut_proveedor', 'like', "%{$filtro}%");
                }

            })
            ->where('saldo_pendiente', '>', 0)     // SOLO PENDIENTES
            ->whereDoesntHave('pagos')             // SIN PAGO REGISTRADO
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return response()->json($documentos);
    }

















}
