<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documentos_financieros', function (Blueprint $table) {
            $table->id();



            $table->string('nro')->nullable();
            $table->string('tipo_doc')->nullable();
            $table->string('tipo_venta')->nullable();
            $table->string('rut_cliente')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('folio')->nullable();
            $table->string('fecha_docto')->nullable();
            $table->string('fecha_recepcion')->nullable();
            $table->string('fecha_acuse_recibo')->nullable();
            $table->string('fecha_reclamo')->nullable();
            $table->string('monto_exento')->nullable();
            $table->string('monto_neto')->nullable();
            $table->string('monto_iva')->nullable();
            $table->string('monto_total')->nullable();
            $table->string('iva_retenido_total')->nullable();
            $table->string('iva_retenido_parcial')->nullable();
            $table->string('iva_no_retenido')->nullable();
            $table->string('iva_propio')->nullable();
            $table->string('iva_terceros')->nullable();
            $table->string('rut_emisor_liquid_factura')->nullable();
            $table->string('neto_comision_liquid_factura')->nullable();
            $table->string('exento_comision_liquid_factura')->nullable();
            $table->string('iva_comision_liquid_factura')->nullable();
            $table->string('iva_fuera_de_plazo')->nullable();
            $table->string('tipo_docto_referencia')->nullable();
            $table->string('folio_docto_referencia')->nullable();
            $table->string('num_ident_receptor_extranjero')->nullable();
            $table->string('nacionalidad_receptor_extranjero')->nullable();
            $table->string('credito_empresa_constructora')->nullable();
            $table->string('impto_zona_franca_ley_18211')->nullable();
            $table->string('garantia_dep_envases')->nullable();
            $table->string('indicador_venta_sin_costo')->nullable();
            $table->string('indicador_servicio_periodico')->nullable();
            $table->string('monto_no_facturable')->nullable();
            $table->string('total_monto_periodo')->nullable();
            $table->string('venta_pasajes_transporte_nacional')->nullable();
            $table->string('venta_pasajes_transporte_internacional')->nullable();
            $table->string('numero_interno')->nullable();
            $table->string('codigo_sucursal')->nullable();
            $table->string('nce_nde_sobre_fact_compra')->nullable();
            $table->string('codigo_otro_imp')->nullable();
            $table->string('valor_otro_imp')->nullable();
            $table->string('tasa_otro_imp')->nullable();
            $table->string('saldo_deuda')->nullable();








            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_financieros');
    }
};
