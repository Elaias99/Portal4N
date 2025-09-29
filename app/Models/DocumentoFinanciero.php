<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoFinanciero extends Model
{
    use HasFactory;

    protected $table = 'documentos_financieros';

    protected $fillable = [
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
        'empresa_id'
        
    ];

    // Relación: un documento pertenece a una cobranza
    public function cobranza()
    {
        return $this->belongsTo(Cobranza::class, 'cobranza_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }



}
