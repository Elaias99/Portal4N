<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class DocumentosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
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

        return [
            $doc->id,
            $doc->empresa?->Nombre,
            $doc->tipoDocumento?->nombre,
            $doc->nro,
            $doc->tipo_venta,
            $doc->rut_cliente,
            $doc->razon_social,
            $doc->folio,

            $this->excelDate($doc->fecha_docto),
            $this->excelDate($doc->fecha_vencimiento),

            $doc->status_original,
            $this->mostrarEstadoActual($doc->status),

            // Fecha en que se ingresa el registro
            $this->excelDate($doc->created_at),

            $doc->monto_exento,
            $doc->monto_neto,
            $doc->monto_iva,
            $doc->monto_total,

            $saldoPendiente,

            $documentoReferencia,
            $referenciadoPor,

            $this->excelDate($doc->fecha_recepcion),
            $this->excelDate($doc->fecha_acuse_recibo),
            $this->excelDate($doc->fecha_reclamo),

            // Fecha que se indica como pago / estado manual
            $this->excelDate($doc->fecha_estado_manual),

            $this->excelDate($doc->fecha_ultima_transaccion),
            $this->excelDate($doc->updated_at),
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
            'Fecha Última Gestión',
            'Actualizado en',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => 'dd-mm-yyyy',  // Fecha Documento
            'J' => 'dd-mm-yyyy',  // Fecha Vencimiento
            'M' => 'dd-mm-yyyy',  // Fecha Estado Manual
            'U' => 'dd-mm-yyyy',  // Fecha Recepción
            'V' => 'dd-mm-yyyy',  // Fecha Acuse Recibo
            'W' => 'dd-mm-yyyy',  // Fecha Reclamo
            'X' => 'dd-mm-yyyy',  // Fecha Pago
            'Y' => 'dd-mm-yyyy',  // Fecha Última Gestión
            'Z' => 'dd-mm-yyyy',  // Actualizado en
        ];
    }

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

    private function format($date)
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : null;
    }

    private function mostrarEstadoActual($estado)
    {
        if ($estado === 'Factory') {
            return 'Factoring';
        }

        return $estado;
    }
}