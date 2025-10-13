<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentoFinanciero;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DocumentosImport;
use App\Models\MovimientoDocumento;
use App\Exports\DocumentosExport;
use Illuminate\Support\Facades\Auth;

class DocumentoFinancieroController extends Controller
{
    //

    public function index(Request $request)
    {
        $query = DocumentoFinanciero::with(['cobranza', 'empresa', 'abonos', 'referenciados']);

        // === FILTROS GENERALES ===
        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', "%{$request->razon_social}%");
        }

        if ($request->filled('rut_cliente')) {
            $query->where('rut_cliente', 'like', "%{$request->rut_cliente}%");
        }

        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [$request->fecha_inicio, $request->fecha_fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_docto', '>=', $request->fecha_inicio);
        } elseif ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_docto', '<=', $request->fecha_fin);
        }

        // === FILTRO FECHA DE VENCIMIENTO ===
        if ($request->filled('vencimiento_inicio') && $request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [$request->vencimiento_inicio, $request->vencimiento_fin]);
        } elseif ($request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->vencimiento_inicio);
        } elseif ($request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->vencimiento_fin);
        }

        // === FILTRO POR ESTADO ORIGINAL ===
        if ($request->filled('status')) {
            $query->where('status_original', $request->status);
        }

        // === CONTADORES (solo status_original, NO status manual) ===
        $totalAlDia = DocumentoFinanciero::where('status_original', 'Al día')->count();
        $totalVencido = DocumentoFinanciero::where('status_original', 'Vencido')->count();

        // === PAGINACIÓN ===
        $documentoFinancieros = $query->orderBy('fecha_docto', 'desc')->paginate(5);

        return view('cobranzas.documentos', compact('documentoFinancieros', 'totalAlDia', 'totalVencido'));
    }









    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        $filename = $request->file('file')->getClientOriginalName();

        // Detectar RUT en el nombre del archivo
        $rut = null;
        if (preg_match('/(\d{7,8}-[0-9Kk])/', $filename, $matches)) {
            $rut = $this->normalizarRut($matches[1]);
        }

        // Buscar empresa con ese RUT
        $empresa = null;
        if ($rut) {
            $empresa = \App\Models\Empresa::whereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '-') = ?", [$rut])->first();
        }

        // Ejecutar importación
        $import = new DocumentosImport($empresa?->id);
        Excel::import($import, $request->file('file'));

        $mensajes = [];

        // 🛑 1️⃣ Si hay errores de estructura, detener inmediatamente
        if (count($import->errores) > 0) {
            foreach ($import->errores as $error) {
                $mensajes[] = "⚠️ " . $error;
            }

            return redirect()->route('cobranzas.documentos')
                ->with('error', 'El archivo no cumple con la estructura esperada.')
                ->with('detalles_errores', $mensajes);
        }

        // ✅ 2️⃣ Construir mensajes informativos
        if (count($import->importados) > 0) {
            $mensajes[] = count($import->importados) . " documentos importados correctamente: " 
                        . implode(', ', $import->importados) . ".";
        }

        if (count($import->duplicados) > 0) {
            $mensajes[] = "Los siguientes folios ya existían y no se importaron: " 
                        . implode(', ', $import->duplicados);
        }

        if (count($import->sinCobranza) > 0) {
            foreach ($import->sinCobranza as $item) {
                $mensajes[] = "No existe cobranza para la razón social '{$item['razon_social']}' (RUT: {$item['rut_cliente']}), 
                    folio {$item['folio']}. <a href='" . route('cobranzas.create') . "' target='_blank'>Cree la cobranza aquí</a>";
            }
        }


        // 🧾 3️⃣ Mensajes específicos para notas de crédito
        if (count($import->notasCredito) > 0) {
            foreach ($import->notasCredito as $nota) {
                $mensajes[] = $nota;
            }
        }


        // ⚠️ 3️⃣ Si hubo observaciones (pero no errores estructurales)
        if (count($mensajes) > 0) {
            return redirect()->route('cobranzas.documentos')
                ->with('warning', 'La importación finalizó con observaciones.')
                ->with('detalles_errores', $mensajes);
        }

        // 🧾 4️⃣ Registrar movimiento solo si todo fue correcto
        if (count($import->importados) > 0) {
            MovimientoDocumento::create([
                'documento_financiero_id' => null,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Importación masiva',
                'descripcion' => "Se importaron " . count($import->importados) . 
                                " documentos desde el archivo '{$filename}' el " . now()->format('d/m/Y H:i:s'),
            ]);
        }

        // 🟢 5️⃣ Mensaje final de éxito
        return redirect()->route('cobranzas.documentos')
            ->with('success', 'Archivo importado correctamente.');
    }




    private function normalizarRut($rut)
    {
        if (!$rut) return null;

        // Quitar puntos y espacios
        $rut = preg_replace('/[^0-9kK-]/', '', $rut);

        // Pasar la K a mayúscula
        return strtoupper($rut);
    }


    public function updateStatus(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'status' => 'nullable|string|max:50',
            'fecha_estado_manual' => 'nullable|date',
        ]);

        $nuevoStatus = $request->status;

        // 🔹 Guardamos cambios en estado manual
        $documento->status = $nuevoStatus;

        // 🔹 Si el estado es manual (Abono, Pago, Cobranza judicial) → guarda la fecha enviada o actual
        if (in_array($nuevoStatus, ['Abono', 'Pago', 'Cobranza judicial'])) {
            $documento->fecha_estado_manual = $request->fecha_estado_manual ?? now();
        } else {
            $documento->fecha_estado_manual = null;
        }

        $original = $documento->getOriginal();

        // Guardar solo si hay cambios
        if ($documento->isDirty(['status', 'fecha_estado_manual'])) {
            $documento->save();

            MovimientoDocumento::create([
                'documento_financiero_id' => $documento->id,
                'user_id' => Auth::id(),
                'tipo_movimiento' => 'Actualización de estado manual',
                'descripcion' => "Estado manual cambiado de '{$original['status']}' a '{$documento->status}'",
                'datos_anteriores' => $original,
                'datos_nuevos' => $documento->getChanges(),
            ]);
        }

        return redirect()->back()->with('success', 'Estado manual actualizado correctamente.');
    }





    public function export()
    {
        $fecha = now()->format('Y-m-d_H-i-s');
        return Excel::download(new DocumentosExport, "documentos_financieros_{$fecha}.xlsx");
    }




    public function storeAbono(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_abono' => 'required|date|before_or_equal:today',
        ], [
            'fecha_abono.before_or_equal' => 'La fecha del abono no debe sobrepasar la fecha actual.',
            'fecha_abono.required' => 'La fecha del abono es obligatoria.',
        ]);

        // Validar que el abono no supere el saldo pendiente
        $saldoPendiente = $documento->saldo_pendiente; // usa el accessor del modelo

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El abono no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }


        // Guardar el abono
        $documento->abonos()->create([
            'monto' => $request->monto,
            'fecha_abono' => $request->fecha_abono,
        ]);

        // Actualizar estado del documento
        $documento->update([
            'status' => 'Abono',
            'fecha_estado_manual' => now(),
        ]);

        // Registrar movimiento
        MovimientoDocumento::create([
            'documento_financiero_id' => $documento->id,
            'user_id' => Auth::id(),
            'tipo_movimiento' => 'Abono registrado',
            'descripcion' => "Se registró un abono de {$request->monto} el {$request->fecha_abono}",
            'datos_nuevos' => ['monto' => $request->monto, 'fecha_abono' => $request->fecha_abono],
        ]);

        return back()->with('success', 'Abono registrado correctamente.');
    }

    public function storeCruce(Request $request, DocumentoFinanciero $documento)
    {
        $request->validate([
            'monto' => 'required|integer|min:1',
            'fecha_cruce' => 'required|date|before_or_equal:today',
        ], [
            'fecha_cruce.before_or_equal' => 'La fecha del cruce no debe sobrepasar la fecha actual.',
            'fecha_cruce.required' => 'La fecha del cruce es obligatoria.',
        ]);

        // Validar que el cruce no supere el saldo pendiente
        $saldoPendiente = $documento->saldo_pendiente;

        if ($request->monto > $saldoPendiente) {
            return back()
                ->withErrors(['monto' => 'El cruce no puede ser mayor al saldo pendiente actual.'])
                ->withInput();
        }

        // Guardar el cruce
        $documento->cruces()->create([
            'monto' => $request->monto,
            'fecha_cruce' => $request->fecha_cruce,
        ]);

        // Actualizar estado del documento
        $documento->update([
            'status' => 'Cruce',
            'fecha_estado_manual' => now(),
        ]);

        // Registrar movimiento
        \App\Models\MovimientoDocumento::create([
            'documento_financiero_id' => $documento->id,
            'user_id' => Auth::id(),
            'tipo_movimiento' => 'Cruce registrado',
            'descripcion' => "Se registró un cruce de {$request->monto} el {$request->fecha_cruce}",
            'datos_nuevos' => ['monto' => $request->monto, 'fecha_cruce' => $request->fecha_cruce],
        ]);

        return back()->with('success', 'Cruce registrado correctamente.');
    }


    public function show(DocumentoFinanciero $documento)
    {
        // Cargar relaciones relevantes
        $documento->load(['empresa', 'abonos', 'cruces', 'referencia', 'referenciados']);

        // Si está referenciado por una nota de crédito o hace referencia a una
        $referencias = [
            'referencia' => $documento->referencia,
            'referenciadoPor' => $documento->referenciados,
        ];

        return view('cobranzas.detalles', compact('documento', 'referencias'));
    }





}
