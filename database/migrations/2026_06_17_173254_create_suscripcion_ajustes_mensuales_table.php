<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_ajustes_mensuales', function (Blueprint $table) {
            $table->id();

            /*
             * Línea/asignación base sobre la que aplica el ajuste.
             * Ejemplo: asignación Colina de José Luis, asignación Pataguas, asignación Benito, etc.
             */
            $table->unsignedBigInteger('suscripcion_asignacion_id');

            /*
             * Periodo al que aplica el ajuste.
             */
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            /*
             * Tipo general de ajuste.
             * Ejemplos posibles:
             * - FACTURACION
             * - CALCULO
             * - INASISTENCIA
             * - REEMPLAZO
             * - OBSERVACION
             */
            $table->string('tipo_ajuste', 50)->nullable();

            /*
             * Sobrescrituras operativas opcionales.
             * Solo se informan si para ese mes se necesita mostrar/generar algo distinto
             * a la asignación maestra.
             */
            $table->string('punto_1')->nullable();
            $table->string('origen_gasto')->nullable();
            $table->string('punto_2')->nullable();
            $table->string('codigo', 100)->nullable();
            $table->string('servicio')->nullable();

            /*
             * Transportista opcional del mes.
             * Útil para reemplazos temporales.
             */
            $table->unsignedBigInteger('suscripcion_transportista_override_id')->nullable();

            /*
             * Proveedor que factura ese mes.
             * Ejemplo: ruta operativa de José Luis, pero mayo se factura por Manuel Hernández.
             */
            $table->unsignedBigInteger('suscripcion_proveedor_facturacion_id')->nullable();

            /*
             * Sobrescrituras tributarias del mes.
             * Esto permite que abril siga como BOLETA aunque el maestro luego pase a FACTURA.
             */
            $table->string('tipo_documento', 50)->nullable();
            $table->string('detalle_documento', 100)->nullable();
            $table->string('detalle_impuesto', 100)->nullable();
            $table->string('final', 100)->nullable();

            /*
             * Grupo documental opcional del mes.
             * Solo usar si ese mes debe caer en una pre-factura/grupo distinto.
             */
            $table->string('grupo_prefactura', 100)->nullable();

            /*
             * Sobrescrituras de cálculo.
             * Si quedan null, se usa el cálculo normal de la asignación.
             */
            $table->bigInteger('costo')->nullable();
            $table->integer('q_calendario')->nullable();
            $table->integer('q_inasistencia')->nullable();
            $table->integer('cantidad')->nullable();
            $table->bigInteger('total')->nullable();

            /*
             * Campo libre para dejar trazabilidad desde el Excel maestro o respaldo.
             */
            $table->text('observacion')->nullable();

            /*
             * Permite desactivar un ajuste sin borrarlo.
             */
            $table->boolean('activo')->default(true);

            $table->timestamps();

            /*
             * Índices y restricciones.
             * Nombres cortos para evitar problemas de largo en MySQL.
             */
            $table->unique(
                ['suscripcion_asignacion_id', 'anio', 'mes'],
                'sus_aj_mens_asig_periodo_unique'
            );

            $table->index(['anio', 'mes'], 'sus_aj_mens_periodo_idx');
            $table->index('suscripcion_proveedor_facturacion_id', 'sus_aj_mens_prov_fact_idx');
            $table->index('suscripcion_transportista_override_id', 'sus_aj_mens_transp_idx');

            $table->foreign('suscripcion_asignacion_id', 'sus_aj_mens_asig_fk')
                ->references('id')
                ->on('suscripcion_asignaciones')
                ->cascadeOnDelete();

            $table->foreign('suscripcion_proveedor_facturacion_id', 'sus_aj_mens_prov_fact_fk')
                ->references('id')
                ->on('suscripcion_proveedores')
                ->nullOnDelete();

            $table->foreign('suscripcion_transportista_override_id', 'sus_aj_mens_transp_fk')
                ->references('id')
                ->on('suscripcion_transportistas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_ajustes_mensuales');
    }
};