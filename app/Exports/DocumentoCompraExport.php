<?php

namespace App\Exports;

use App\Models\DocumentoCompra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

class DocumentoCompraExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected $documentos;

    public function __construct($documentos)
    {
        $this->documentos = $documentos;
    }

    /**
     * Colección de registros
     */
    public function collection()
    {
        return $this->documentos;
    }

    /**
     * Mapeo de filas del Excel (similar al exportador financiero)
     */
    public function map($doc): array
    {
        // === Saldo pendiente dinámico ===
        if ($doc->tipo_documento_id == 61) {
            // Nota de crédito → saldo 0
            $saldoPendiente = 0;
        } else {
            $saldoPendiente = $doc->saldo_pendiente;
        }

        // === Documento Referencia (si este documento referencia otro) ===
        $documentoReferencia = null;
        if ($doc->referencia) {
            $ref = $doc->referencia;
            $documentoReferencia = "{$ref->tipoDocumento?->nombre} folio {$ref->folio}";
            if ($ref->fecha_docto) {
                $documentoReferencia .= " ({$this->format($ref->fecha_docto)})";
            }
        }

        // === Referenciado Por (si tiene notas asociadas) ===
        $referenciadoPor = null;
        if ($doc->referenciados->isNotEmpty()) {
            $referenciadoPor = $doc->referenciados->map(function ($ref) {
                $monto = number_format($ref->monto_total, 0, ',', '.');
                return $ref->tipoDocumento?->nombre . ' folio ' . $ref->folio . ' ($' . $monto . ')';
            })->join(', ');
        }

        return [
            $doc->id,
            $doc->empresa?->Nombre,
            $doc->tipoDocumento?->nombre,
            $doc->nro,
            $doc->tipo_compra,
            $doc->rut_proveedor,
            $doc->razon_social,
            $doc->folio,

            // Fechas base
            $this->format($doc->fecha_docto),
            $this->format($doc->fecha_vencimiento),

            // Estados
            $doc->status_original,
            $doc->estado,
            $this->format($doc->fecha_estado_manual),

            // Montos
            $doc->monto_exento,
            $doc->monto_neto,
            $doc->monto_iva_recuperable,
            $doc->monto_iva_no_recuperable,
            $doc->monto_total,

            // Saldo
            $saldoPendiente,

            // Referencias
            $documentoReferencia,
            $referenciadoPor,

            // Fechas administrativas del registro
            $this->format($doc->fecha_recepcion),
            $this->format($doc->fecha_acuse),

            // Timestamps
            $this->format($doc->created_at),
            $this->format($doc->updated_at),
        ];
    }

    /**
     * Encabezados del Excel
     */
    public function headings(): array
    {
        return [
            'ID',
            'Empresa',
            'Tipo Documento',
            'Nro',
            'Tipo Compra',
            'RUT Proveedor',
            'Razón Social',
            'Folio',
            'Fecha Documento',
            'Fecha Vencimiento',
            'Estado Original',
            'Estado Actual',
            'Fecha Estado Manual',
            'Monto Exento',
            'Monto Neto',
            'Monto IVA Recuperable',
            'Monto IVA No Recuperable',
            'Monto Total',
            'Saldo Pendiente',
            'Documento Referencia',
            'Referenciado Por',
            'Fecha Recepción',
            'Fecha Acuse',
            'Creado en',
            'Actualizado en',
        ];
    }

    /**
     * Formateo seguro de fechas
     */
    private function format($date)
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : null;
    }
}