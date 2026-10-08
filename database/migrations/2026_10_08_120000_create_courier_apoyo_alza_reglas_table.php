<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Reglas de Apoyo Alza: a qué proveedor se le da apoyo, sobre qué
     * (Acuerdos, Variables o Ruta CV) y cuánto (porcentaje o monto por
     * día). No dependen del período: casi no cambian de un mes a otro.
     * Al calcular un período, cada regla se convierte en una fila de
     * courier_pagos_proceso (proceso Apoyo Alza) con su monto.
     *
     * Se cargan con la plantilla de Apoyo Alza de LogisticaCL; cada carga
     * reemplaza la lista completa.
     */
    public function up(): void
    {
        Schema::create('courier_apoyo_alza_reglas', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('fila_origen')->nullable();

            /* El proveedor como viene en la plantilla. */
            $table->string('proveedor', 255)->nullable();
            $table->string('rut_proveedor', 25)->nullable();
            $table->string('zona', 20)->nullable();

            /* Acuerdos | Variables | Ruta CV */
            $table->string('proceso_base', 20);

            /* Sólo Acuerdos: servicios del acuerdo, separados por «|». */
            $table->string('servicio_acuerdo', 255)->nullable();

            /* % | Dia de Ruta CV */
            $table->string('factor', 20);
            $table->decimal('porcentaje', 9, 6)->nullable();
            $table->unsignedInteger('monto_dia')->nullable();

            $table->string('empresa_mandante', 100)->nullable();
            $table->string('agencia', 150)->nullable();

            $table->string('archivo_origen', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_apoyo_alza_reglas');
    }
};
