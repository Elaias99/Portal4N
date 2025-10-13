<?php

namespace App\Exports;

use App\Models\DocumentoFinanciero;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class DocumentosExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * 🔹 Retorna la colección de registros a exportar
     */
    public function collection()
    {
        // ✅ Cargamos todas las relaciones necesarias
        return DocumentoFinanciero::with([
            'empresa',
            'abonos',
            'cruces',
            'tipoDocumento',
            'referencia',      // Documento referenciado (ej: factura)
            'referenciados',   // Documentos que lo referencian (ej: notas de crédito)
            'cobranza'
        ])->get();
    }

    /**
     * 🔹 Define cómo se exporta cada fila
     */
    public function map($doc): array
    {
        // === Cálculos de abonos ===
        $totalAbonado = $doc->abonos->sum('monto');
        $ultimaFechaAbono = $doc->abonos->max('fecha_abono');

        // === Cálculos de cruces ===
        $totalCruzado = $doc->cruces->sum('monto');
        $ultimaFechaCruce = $doc->cruces->max('fecha_cruce');

        // === Saldo pendiente dinámico ===
        if ($doc->tipo_documento_id == 61) {
            // 🧾 Nota de Crédito Electrónica → mostrar saldo 0 en la exportación
            $saldoPendiente = 0;
        } else {
            // 🟢 Otros documentos → calcular normalmente
            $saldoPendiente = $doc->saldo_pendiente;
        }

        // === Documento Referencia (si este documento apunta a otro) ===
        $documentoReferencia = null;
        if ($doc->referencia) {
            $ref = $doc->referencia;
            $documentoReferencia = "{$ref->tipoDocumento?->nombre} folio {$ref->folio}";
            if ($ref->fecha_docto) {
                $documentoReferencia .= " ({$ref->fecha_docto})";
            }
        }

        // === Referenciado Por (si este documento tiene otros que lo referencian) ===
        $referenciadoPor = null;
        if ($doc->referenciados->isNotEmpty()) {
            $referenciadoPor = $doc->referenciados->map(function ($ref) {
                $monto = number_format($ref->monto_total, 0, ',', '.');
                return $ref->tipoDocumento?->nombre . ' folio ' . $ref->folio . ' ($' . $monto . ')';
            })->join(', ');
        }

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

            // 🔹 Estados
            $doc->status_original,
            $doc->status,

            // 🔹 Fechas administrativas
            $doc->fecha_recepcion,
            $doc->fecha_acuse_recibo,
            $doc->fecha_reclamo,

            // 🔹 Montos
            $doc->monto_exento,
            $doc->monto_neto,
            $doc->monto_iva,
            $doc->monto_total,

            // 🔹 Datos de abonos
            $totalAbonado,
            $ultimaFechaAbono ? Carbon::parse($ultimaFechaAbono)->format('Y-m-d') : null,

            // 🔹 Datos de cruces
            $totalCruzado,
            $ultimaFechaCruce ? Carbon::parse($ultimaFechaCruce)->format('Y-m-d') : null,

            // 🔹 Saldo pendiente (con control para notas de crédito)
            $saldoPendiente,

            // 🔹 Columnas de referencia
            $documentoReferencia,
            $referenciadoPor,

            // 🔹 Otros campos
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
            $doc->cobranza->servicio,
            $doc->empresa?->Nombre ?? 'Sin empresa',
            $doc->created_at,
            $doc->updated_at,
        ];
    }

    /**
     * 🔹 Encabezados de las columnas en el Excel
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
            'Estado Original',
            'Estado Actual',
            'Fecha Recepción',
            'Fecha Acuse Recibo',
            'Fecha Reclamo',
            'Monto Exento',
            'Monto Neto',
            'Monto IVA',
            'Monto Total',
            'Total Abonado',
            'Última Fecha de Abono',
            'Total Cruzado',
            'Última Fecha de Cruce',
            'Saldo Pendiente',
            'Documento Referencia',
            'Referenciado Por',
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
            'Servicio',
            'Empresa Nombre',
            'Creado en',
            'Actualizado en',
        ];
    }
}
