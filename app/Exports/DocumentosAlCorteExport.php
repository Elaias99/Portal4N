<?php

namespace App\Exports;

use App\Models\DocumentoFinanciero;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentosAlCorteExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Request $request;
    protected Carbon $fechaCorte;

    public function __construct(Request $request, Carbon $fechaCorte)
    {
        $this->request = $request;
        $this->fechaCorte = $fechaCorte;
    }

    public function collection()
    {
        $query = DocumentoFinanciero::with([
            'empresa',
            'tipoDocumento',
            'referencia.tipoDocumento',
            'referenciados.tipoDocumento',
            'abonos',
            'cruces',
            'pagos',
            'prontoPagos',
        ]);

        $query->whereDate('fecha_docto', '<=', $this->fechaCorte->toDateString());

        $this->aplicarFiltrosBase($query);

        $documentos = $query
            ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento DESC')
            ->orderBy('folio', 'DESC')
            ->get();

        return $documentos
            ->filter(fn ($doc) => $this->cumpleFiltrosHistoricos($doc))
            ->values();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Empresa',
            'Tipo Documento',
            'Nro',
            'Tipo Venta',
            'RUT Cliente',
            'Razón Social',
            'Folio',
            'Fecha Documento',
            'Fecha Vencimiento',
            'Estado Original',
            'Estado Actual',
            'Monto Exento',
            'Monto Neto',
            'Monto IVA',
            'Monto Total',
            'Saldo Pendiente',
            'Documento Referencia',
            'Referenciado Por',
            'Fecha Recepción',
            'Fecha Acuse Recibo',
            'Fecha Reclamo',
            'Fecha Pago',
        ];
    }

    public function map($doc): array
    {
        $estadoOriginal = $this->obtenerEstadoOriginalAlCorte($doc);
        $estadoActual = $this->obtenerEstadoActualAlCorte($doc);
        $saldoPendiente = $this->calcularSaldoPendienteAlCorte($doc);
        $documentoReferencia = $this->obtenerDocumentoReferenciaTextoAlCorte($doc);
        $referenciadoPor = $this->obtenerReferenciadoPorTextoAlCorte($doc);
        $fechaPago = $this->obtenerFechaPagoAlCorte($doc);

        return [
            $doc->id,
            $doc->empresa?->Nombre,
            $doc->tipoDocumento?->nombre,
            $doc->nro,
            $doc->tipo_venta,
            $doc->rut_cliente,
            $doc->razon_social,
            $doc->folio,
            $this->format($doc->fecha_docto),
            $this->format($doc->fecha_vencimiento),
            $estadoOriginal,
            $estadoActual,
            $doc->monto_exento,
            $doc->monto_neto,
            $doc->monto_iva,
            $doc->monto_total,
            $saldoPendiente,
            $documentoReferencia,
            $referenciadoPor,
            $this->format($doc->fecha_recepcion),
            $this->format($doc->fecha_acuse_recibo),
            $this->format($doc->fecha_reclamo),
            $this->format($fechaPago),
        ];
    }

    private function aplicarFiltrosBase(Builder $query): void
    {
        if ($this->request->filled('razon_social')) {
            $busqueda = $this->request->razon_social;
            $busquedaNormalizada = preg_replace('/[^a-zA-Z0-9\s]/u', '', $busqueda);

            $query->whereRaw("
                REPLACE(REPLACE(REPLACE(razon_social, '.', ''), ',', ''), '  ', ' ')
                LIKE ?
            ", ["%{$busquedaNormalizada}%"]);
        }

        if ($this->request->filled('rut_cliente')) {
            $query->where('rut_cliente', 'like', '%' . $this->request->rut_cliente . '%');
        }

        if ($this->request->filled('folio')) {
            $query->where('folio', 'like', '%' . $this->request->folio . '%');
        }

        if ($this->request->filled('fecha_inicio') && $this->request->filled('fecha_fin')) {
            $query->whereBetween('fecha_docto', [
                $this->request->fecha_inicio,
                $this->request->fecha_fin,
            ]);
        } elseif ($this->request->filled('fecha_inicio')) {
            $query->whereDate('fecha_docto', '>=', $this->request->fecha_inicio);
        } elseif ($this->request->filled('fecha_fin')) {
            $query->whereDate('fecha_docto', '<=', $this->request->fecha_fin);
        }

        if ($this->request->filled('vencimiento_inicio') && $this->request->filled('vencimiento_fin')) {
            $query->whereBetween('fecha_vencimiento', [
                $this->request->vencimiento_inicio,
                $this->request->vencimiento_fin,
            ]);
        } elseif ($this->request->filled('vencimiento_inicio')) {
            $query->whereDate('fecha_vencimiento', '>=', $this->request->vencimiento_inicio);
        } elseif ($this->request->filled('vencimiento_fin')) {
            $query->whereDate('fecha_vencimiento', '<=', $this->request->vencimiento_fin);
        }
    }

    private function cumpleFiltrosHistoricos($doc): bool
    {
        if ($this->request->filled('saldo_valor')) {
            $valor = (float) str_replace(['.', ','], '', $this->request->saldo_valor);
            $tipoSaldo = $this->request->input('saldo_tipo', 'saldo_pendiente');

            $valorComparar = $tipoSaldo === 'monto_total'
                ? (float) $doc->monto_total
                : (float) $this->calcularSaldoPendienteAlCorte($doc);

            if ($valorComparar < ($valor - 1) || $valorComparar > ($valor + 1)) {
                return false;
            }
        }

        if ($this->request->filled('status')) {
            if ($this->obtenerEstadoOriginalAlCorte($doc) !== $this->request->status) {
                return false;
            }
        }

        if ($this->request->filled('estado_pago')) {
            $saldo = $this->calcularSaldoPendienteAlCorte($doc);

            if ($this->request->estado_pago === 'Pagado' && $saldo > 0) {
                return false;
            }

            if ($this->request->estado_pago === 'Pendiente' && $saldo <= 0) {
                return false;
            }
        }

        return true;
    }

    private function obtenerPagosAlCorte($doc): Collection
    {
        return $doc->pagos
            ->filter(fn ($pago) => $pago->fecha_pago && Carbon::parse($pago->fecha_pago)->lte($this->fechaCorte))
            ->values();
    }

    private function obtenerProntoPagosAlCorte($doc): Collection
    {
        return $doc->prontoPagos
            ->filter(fn ($pp) => $pp->fecha_pronto_pago && Carbon::parse($pp->fecha_pronto_pago)->lte($this->fechaCorte))
            ->values();
    }

    private function obtenerAbonosAlCorte($doc): Collection
    {
        return $doc->abonos
            ->filter(fn ($abono) => $abono->fecha_abono && Carbon::parse($abono->fecha_abono)->lte($this->fechaCorte))
            ->values();
    }

    private function obtenerCrucesAlCorte($doc): Collection
    {
        return $doc->cruces
            ->filter(fn ($cruce) => $cruce->fecha_cruce && Carbon::parse($cruce->fecha_cruce)->lte($this->fechaCorte))
            ->values();
    }

    private function obtenerReferenciadosAlCorte($doc): Collection
    {
        return $doc->referenciados
            ->filter(fn ($ref) => $ref->fecha_docto && Carbon::parse($ref->fecha_docto)->lte($this->fechaCorte))
            ->values();
    }

    private function calcularSaldoPendienteAlCorte($doc): int
    {
        if ((int) $doc->tipo_documento_id === 61) {
            return 0;
        }

        if ($this->obtenerPagosAlCorte($doc)->isNotEmpty()) {
            return 0;
        }

        if ($this->obtenerProntoPagosAlCorte($doc)->isNotEmpty()) {
            return 0;
        }

        $saldo = (int) ($doc->monto_total ?? 0);

        $referenciados = $this->obtenerReferenciadosAlCorte($doc);

        $saldo -= (int) $referenciados
            ->where('tipo_documento_id', 61)
            ->sum('monto_total');

        $saldo += (int) $referenciados
            ->where('tipo_documento_id', 56)
            ->sum('monto_total');

        $saldo -= (int) $this->obtenerAbonosAlCorte($doc)->sum('monto');
        $saldo -= (int) $this->obtenerCrucesAlCorte($doc)->sum('monto');

        return max($saldo, 0);
    }

    private function obtenerEstadoOriginalAlCorte($doc): string
    {
        if (!$doc->fecha_vencimiento) {
            return 'Sin cálculo';
        }

        return Carbon::parse($doc->fecha_vencimiento)->lt($this->fechaCorte->copy()->startOfDay())
            ? 'Vencido'
            : 'Al día';
    }

    private function obtenerEstadoActualAlCorte($doc): ?string
    {
        if ((int) $doc->tipo_documento_id === 61) {
            return null;
        }

        if ($this->obtenerPagosAlCorte($doc)->isNotEmpty()) {
            return 'Pago';
        }

        if ($this->obtenerProntoPagosAlCorte($doc)->isNotEmpty()) {
            return 'Pronto pago';
        }

        $abonos = $this->obtenerAbonosAlCorte($doc);
        $cruces = $this->obtenerCrucesAlCorte($doc);

        if ($abonos->isEmpty() && $cruces->isEmpty()) {
            return null;
        }

        $ultimaFechaAbono = $abonos->max('fecha_abono');
        $ultimaFechaCruce = $cruces->max('fecha_cruce');

        if ($ultimaFechaAbono && $ultimaFechaCruce) {
            return Carbon::parse($ultimaFechaAbono)->gte(Carbon::parse($ultimaFechaCruce))
                ? 'Abono'
                : 'Cruce';
        }

        if ($ultimaFechaAbono) {
            return 'Abono';
        }

        if ($ultimaFechaCruce) {
            return 'Cruce';
        }

        return null;
    }

    private function obtenerFechaPagoAlCorte($doc): ?string
    {
        $fechas = collect();

        $ultimoPago = $this->obtenerPagosAlCorte($doc)->max('fecha_pago');
        $ultimoProntoPago = $this->obtenerProntoPagosAlCorte($doc)->max('fecha_pronto_pago');

        foreach ([$ultimoPago, $ultimoProntoPago] as $fecha) {
            if ($fecha) {
                $fechas->push($fecha);
            }
        }

        return $fechas->filter()->sortDesc()->first();
    }

    private function obtenerDocumentoReferenciaTextoAlCorte($doc): ?string
    {
        if (!$doc->referencia) {
            return null;
        }

        if (!$doc->referencia->fecha_docto || Carbon::parse($doc->referencia->fecha_docto)->gt($this->fechaCorte)) {
            return null;
        }

        $ref = $doc->referencia;

        $texto = ($ref->tipoDocumento?->nombre ?? 'Documento') . ' folio ' . $ref->folio;

        if ($ref->fecha_docto) {
            $texto .= ' (' . $this->format($ref->fecha_docto) . ')';
        }

        return $texto;
    }

    private function obtenerReferenciadoPorTextoAlCorte($doc): ?string
    {
        $referenciados = $this->obtenerReferenciadosAlCorte($doc);

        if ($referenciados->isEmpty()) {
            return null;
        }

        return $referenciados->map(function ($ref) {
            $monto = number_format((int) $ref->monto_total, 0, ',', '.');

            return ($ref->tipoDocumento?->nombre ?? 'Documento')
                . ' folio ' . $ref->folio
                . ' ($' . $monto . ')';
        })->join(', ');
    }

    private function format($date): ?string
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : null;
    }
}