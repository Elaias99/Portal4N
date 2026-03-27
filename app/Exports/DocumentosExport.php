<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

class DocumentosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected $documentos;

    public function __construct($documentos)
    {
        $this->documentos = $documentos;
    }

    public function collection()
    {
        return $this->documentos;
    }

    public function map($doc): array
    {
        $saldoPendiente = (int) $doc->tipo_documento_id === 61
            ? 0
            : $doc->saldo_pendiente;

        $documentoReferencia = null;
        if ($doc->referencia) {
            $ref = $doc->referencia;
            $documentoReferencia = "{$ref->tipoDocumento?->nombre} folio {$ref->folio}";
            if ($ref->fecha_docto) {
                $documentoReferencia .= " ({$this->format($ref->fecha_docto)})";
            }
        }

        $referenciadoPor = null;
        if ($doc->referenciados && $doc->referenciados->isNotEmpty()) {
            $referenciadoPor = $doc->referenciados->map(function ($ref) {
                $monto = number_format($ref->monto_total, 0, ',', '.');
                return ($ref->tipoDocumento?->nombre ?? 'Documento')
                    . ' folio ' . $ref->folio
                    . ' ($' . $monto . ')';
            })->join(', ');
        }

        $fechaPago = $doc->pagos && $doc->pagos->isNotEmpty()
            ? $doc->pagos->max('fecha_pago')
            : null;

        $fechaProntoPago = $doc->prontoPagos && $doc->prontoPagos->isNotEmpty()
            ? $doc->prontoPagos->max('fecha_pronto_pago')
            : null;

        $fechaUltimaGestion = $doc->fecha_ultima_transaccion ?? null;

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

            $doc->status_original,
            $doc->status,
            $this->format($doc->fecha_estado_manual),

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
            $this->format($fechaProntoPago),
            $this->format($fechaUltimaGestion),

            $this->format($doc->created_at),
            $this->format($doc->updated_at),
        ];
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
            'Fecha Estado Manual',
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
            'Fecha Pronto Pago',
            'Fecha Última Gestión',
            'Creado en',
            'Actualizado en',
        ];
    }

    private function format($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d') : null;
    }
}