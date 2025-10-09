<?php

namespace App\Exports;

use App\Models\DocumentoFinanciero;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentosExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Retorna la colección de registros a exportar
     */
    public function collection()
    {
        // Traemos empresa y abonos
        return DocumentoFinanciero::with(['empresa', 'abonos', 'tipoDocumento'])->get();

    }


    /**
     * Define cómo se exporta cada fila
     */
    public function map($doc): array
    {
        $totalAbonado = $doc->abonos->sum('monto');
        $saldoPendiente = $doc->saldo_pendiente; // ✅ Usa el accessor del modelo

        $ultimaFechaAbono = $doc->abonos->max('fecha_abono');

        return [
            $doc->id,
            $doc->nro,
            $doc->tipoDocumento?->nombre ?? 'Sin tipo',
            $doc->tipo_venta,
            $doc->rut_cliente,
            $doc->razon_social,
            $doc->folio,
            $doc->fecha_docto,
            $doc->fecha_vencimiento,

            // 🔹 Nuevo: estados claros
            $doc->status_original,   // Estado al importar (Al día / Vencido)
            $doc->status,            // Estado actual (puede ser Abono, Pago, etc.)

            $doc->fecha_recepcion,
            $doc->fecha_acuse_recibo,
            $doc->fecha_reclamo,
            $doc->monto_exento,
            $doc->monto_neto,
            $doc->monto_iva,
            $doc->monto_total,

            // 🔹 Datos de abonos
            $totalAbonado,
            $saldoPendiente,
            $ultimaFechaAbono ? \Carbon\Carbon::parse($ultimaFechaAbono)->format('Y-m-d') : null,

            // 🔹 Resto de columnas
            $doc->iva_retenido_total,
            $doc->iva_retenido_parcial,
            $doc->iva_no_retenido,
            $doc->iva_propio,
            $doc->iva_terceros,
            $doc->rut_emisor_liquid_factura,
            $doc->neto_comision_liquid_factura,
            $doc->exento_comision_liquid_factura,
            $doc->iva_comision_liquid_factura,
            $doc->iva_fuera_de_plazo,
            $doc->tipo_docto_referencia,
            $doc->folio_docto_referencia,
            $doc->num_ident_receptor_extranjero,
            $doc->nacionalidad_receptor_extranjero,
            $doc->credito_empresa_constructora,
            $doc->impto_zona_franca_ley_18211,
            $doc->garantia_dep_envases,
            $doc->indicador_venta_sin_costo,
            $doc->indicador_servicio_periodico,
            $doc->monto_no_facturable,
            $doc->total_monto_periodo,
            $doc->venta_pasajes_transporte_nacional,
            $doc->venta_pasajes_transporte_internacional,
            $doc->numero_interno,
            $doc->codigo_sucursal,
            $doc->nce_nde_sobre_fact_compra,
            $doc->codigo_otro_imp,
            $doc->valor_otro_imp,
            $doc->tasa_otro_imp,
            $doc->cobranza_id,
            $doc->empresa?->Nombre ?? 'Sin empresa',
            $doc->created_at,
            $doc->updated_at,
        ];
    }



    /**
     * Encabezados de las columnas en el Excel
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nro',
            'Tipo Documento',
            'Tipo Venta',
            'RUT Cliente',
            'Razón Social',
            'Folio',
            'Fecha Documento',
            'Fecha Vencimiento',
            'Estado Original',    // Nuevo encabezado
            'Estado Actual',      // Nuevo encabezado
            'Fecha Recepción',
            'Fecha Acuse Recibo',
            'Fecha Reclamo',
            'Monto Exento',
            'Monto Neto',
            'Monto IVA',
            'Monto Total',
            'Total Abonado',
            'Saldo Pendiente',
            'Última Fecha de Abono',
            'IVA Retenido Total',
            'IVA Retenido Parcial',
            'IVA No Retenido',
            'IVA Propio',
            'IVA Terceros',
            'RUT Emisor Liquid. Factura',
            'Neto Comisión Liquid. Factura',
            'Exento Comisión Liquid. Factura',
            'IVA Comisión Liquid. Factura',
            'IVA Fuera de Plazo',
            'Tipo Docto. Referencia',
            'Folio Docto. Referencia',
            'N° Ident. Receptor Extranjero',
            'Nacionalidad Receptor Extranjero',
            'Crédito Empresa Constructora',
            'Impto Zona Franca Ley 18211',
            'Garantía Dep. Envases',
            'Indicador Venta sin Costo',
            'Indicador Servicio Periódico',
            'Monto no Facturable',
            'Total Monto Periodo',
            'Venta Pasajes Transporte Nacional',
            'Venta Pasajes Transporte Internacional',
            'Número Interno',
            'Código Sucursal',
            'NCE/NDE sobre Fact. Compra',
            'Código Otro Impuesto',
            'Valor Otro Impuesto',
            'Tasa Otro Impuesto',
            'Cobranza ID',
            'Empresa Nombre',
            'Creado en',
            'Actualizado en',
        ];
    }

}
