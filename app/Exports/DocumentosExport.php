<?php

namespace App\Exports;

use App\Models\DocumentoFinanciero;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DocumentosExport implements FromCollection, WithHeadings
{
    /**
     * Retorna la colección de registros a exportar
     */
    public function collection()
    {
        return DocumentoFinanciero::select([
            'id',
            'nro',
            'tipo_doc',
            'tipo_venta',
            'rut_cliente',
            'razon_social',
            'folio',
            'fecha_docto',
            'fecha_recepcion',
            'fecha_acuse_recibo',
            'fecha_reclamo',
            'monto_exento',
            'monto_neto',
            'monto_iva',
            'monto_total',
            'iva_retenido_total',
            'iva_retenido_parcial',
            'iva_no_retenido',
            'iva_propio',
            'iva_terceros',
            'rut_emisor_liquid_factura',
            'neto_comision_liquid_factura',
            'exento_comision_liquid_factura',
            'iva_comision_liquid_factura',
            'iva_fuera_de_plazo',
            'tipo_docto_referencia',
            'folio_docto_referencia',
            'num_ident_receptor_extranjero',
            'nacionalidad_receptor_extranjero',
            'credito_empresa_constructora',
            'impto_zona_franca_ley_18211',
            'garantia_dep_envases',
            'indicador_venta_sin_costo',
            'indicador_servicio_periodico',
            'monto_no_facturable',
            'total_monto_periodo',
            'venta_pasajes_transporte_nacional',
            'venta_pasajes_transporte_internacional',
            'numero_interno',
            'codigo_sucursal',
            'nce_nde_sobre_fact_compra',
            'codigo_otro_imp',
            'valor_otro_imp',
            'tasa_otro_imp',
            'cobranza_id',
            'empresa_id',
            'status', // 👈 nuestro campo nuevo
            'created_at',
            'updated_at',
        ])->get();
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
            'Fecha Recepción',
            'Fecha Acuse Recibo',
            'Fecha Reclamo',
            'Monto Exento',
            'Monto Neto',
            'Monto IVA',
            'Monto Total',
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
            'Empresa ID',
            'Status',
            'Creado en',
            'Actualizado en',
        ];
    }
}
