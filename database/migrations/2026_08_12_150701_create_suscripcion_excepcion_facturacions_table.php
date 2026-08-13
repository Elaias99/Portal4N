<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_excepciones_facturacion', function (Blueprint $table) {
            $table->id();

            /*
             * Asignación original.
             * Ejemplo: BH.01
             */
            $table->unsignedBigInteger('suscripcion_asignacion_id');

            /*
             * Fecha exacta de la ejecución excepcional.
             * Ejemplo: 2026-07-25
             */
            $table->date('fecha');

            /*
             * Proveedor que cobrará esta ejecución.
             * Ejemplo: Sanrey.
             */
            $table->unsignedBigInteger(
                'suscripcion_proveedor_facturacion_id'
            );

            /*
             * Transportista efectivo de esa fecha.
             * NULL = mantener el original.
             */
            $table->unsignedBigInteger(
                'suscripcion_transportista_override_id'
            )->nullable();

            /*
             * NULL = usar costo habitual de la asignación.
             */
            $table->integer('costo')->nullable();

            /*
             * Snapshot / override documental.
             */
            $table->string('tipo_documento')->nullable();
            $table->string('detalle_documento')->nullable();
            $table->string('detalle_impuesto')->nullable();
            $table->string('final')->nullable();

            $table->text('observacion')->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            /*
             * Foreign keys con nombres cortos explícitos.
             */
            $table->foreign(
                'suscripcion_asignacion_id',
                'sus_exc_fact_asig_fk'
            )
                ->references('id')
                ->on('suscripcion_asignaciones')
                ->restrictOnDelete();

            $table->foreign(
                'suscripcion_proveedor_facturacion_id',
                'sus_exc_fact_prov_fk'
            )
                ->references('id')
                ->on('suscripcion_proveedores')
                ->restrictOnDelete();

            $table->foreign(
                'suscripcion_transportista_override_id',
                'sus_exc_fact_trans_fk'
            )
                ->references('id')
                ->on('suscripcion_transportistas')
                ->restrictOnDelete();

            /*
             * Una asignación sólo puede tener una excepción
             * de facturación para una misma fecha.
             */
            $table->unique(
                [
                    'suscripcion_asignacion_id',
                    'fecha',
                ],
                'sus_exc_fact_asig_fecha_uq'
            );

            /*
             * Índices de consulta.
             */
            $table->index(
                'fecha',
                'sus_exc_fact_fecha_idx'
            );

            $table->index(
                [
                    'fecha',
                    'suscripcion_proveedor_facturacion_id',
                ],
                'sus_exc_fact_fecha_prov_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'suscripcion_excepciones_facturacion'
        );
    }
};