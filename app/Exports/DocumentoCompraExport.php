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

    /**
     * 🔹 Colección de registros a exportar
     */
    public function collection()
    {
        return DocumentoCompra::with(['empresa', 'tipoDocumento'])->get();
    }

    /**
     * 🔹 Definición de encabezados de las columnas en Excel
     */
    public function headings(): array
    {
        return [
            'Empresa',
            'Tipo Documento',
            'Nro',
            'Tipo Compra',
            'RUT Proveedor',
            'Razón Social',
            'Folio',
            'Fecha Docto',
            'Fecha Recepción',
            'Fecha Acuse',
            'Monto Exento',
            'Monto Neto',
            'Monto IVA Recuperable',
            'Monto IVA No Recuperable',
            'Código IVA No Rec.',
            'Monto Total',
            'Monto Neto Activo Fijo',
            'IVA Activo Fijo',
            'IVA Uso Común',
            'Impto. sin Derecho a Crédito',
            'IVA No Retenido',
            'Tabacos Puros',
            'Tabacos Cigarrillos',
            'Tabacos Elaborados',
            'NCE o NDE sobre Fact. de Compra',
            'Código Otro Impuesto',
            'Valor Otro Impuesto',
            'Tasa Otro Impuesto',
            'Fecha Creación',
            'Última Actualización',
        ];
    }

    /**
     * 🔹 Mapeo de los datos por fila
     */
    public function map($doc): array
    {
        return [
            $doc->empresa?->Nombre ?? 'Sin empresa',
            $doc->tipoDocumento?->nombre ?? 'Sin tipo',
            $doc->nro,
            $doc->tipo_compra,
            $doc->rut_proveedor,
            $doc->razon_social,
            $doc->folio,
            $this->formatDate($doc->fecha_docto),
            $this->formatDate($doc->fecha_recepcion),
            $this->formatDate($doc->fecha_acuse),
            $doc->monto_exento,
            $doc->monto_neto,
            $doc->monto_iva_recuperable,
            $doc->monto_iva_no_recuperable,
            $doc->codigo_iva_no_rec,
            $doc->monto_total,
            $doc->monto_neto_activo_fijo,
            $doc->iva_activo_fijo,
            $doc->iva_uso_comun,
            $doc->impto_sin_derecho_credito,
            $doc->iva_no_retenido,
            $doc->tabacos_puros,
            $doc->tabacos_cigarrillos,
            $doc->tabacos_elaborados,
            $doc->nce_nde_sobre_fact_compra,
            $doc->codigo_otro_impuesto,
            $doc->valor_otro_impuesto,
            $doc->tasa_otro_impuesto,
            $this->formatDate($doc->created_at),
            $this->formatDate($doc->updated_at),
        ];
    }

    /**
     * 🔹 Formateo seguro de fechas
     */
    private function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : '-';
    }
}
