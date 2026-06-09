<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class DocumentoCompraExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
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
     * Mapeo de filas del Excel
     */
    public function map($doc): array
    {
        // === Saldo pendiente dinámico ===
        if ((int) $doc->tipo_documento_id === 61) {
            // Nota de crédito → saldo 0
            $saldoPendiente = 0;
        } else {
            $saldoPendiente = (int) (
                $doc->saldo_pendiente_export
                ?? $doc->getRawOriginal('saldo_pendiente')
                ?? 0
            );
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

        if ($doc->referenciados && $doc->referenciados->isNotEmpty()) {
            $referenciadoPor = $doc->referenciados->map(function ($ref) {
                $monto = number_format($ref->monto_total, 0, ',', '.');

                return $ref->tipoDocumento?->nombre
                    . ' folio ' . $ref->folio
                    . ' ($' . $monto . ')';
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
            $this->excelDate($doc->fecha_docto),
            $this->excelDate($doc->fecha_vencimiento),

            // Estados
            $doc->status_original,
            $doc->estado,
            $this->excelDate($doc->fecha_estado_manual),

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
            $this->excelDate($doc->fecha_recepcion),
            $this->excelDate($doc->fecha_acuse),

            // Timestamps
            $this->excelDate($doc->created_at),
            $this->excelDate($doc->updated_at),
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
     * Formato real de fechas en Excel.
     */
    public function columnFormats(): array
    {
        return [
            'I' => 'dd-mm-yyyy', // Fecha Documento
            'J' => 'dd-mm-yyyy', // Fecha Vencimiento
            'M' => 'dd-mm-yyyy', // Fecha Estado Manual
            'V' => 'dd-mm-yyyy', // Fecha Recepción
            'W' => 'dd-mm-yyyy', // Fecha Acuse
            'X' => 'dd-mm-yyyy', // Creado en
            'Y' => 'dd-mm-yyyy', // Actualizado en
        ];
    }

    /**
     * Fecha real para Excel.
     */
    private function excelDate($date)
    {
        if (!$date) {
            return null;
        }

        try {
            return ExcelDate::dateTimeToExcel(Carbon::parse($date));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Formateo seguro de fechas para textos internos,
     * por ejemplo: Documento Referencia.
     */
    private function format($date)
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : null;
    }
}