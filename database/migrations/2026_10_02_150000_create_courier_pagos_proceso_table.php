<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Los pagos del mes que no salen de Geolice ni de Acuerdos: Ruta CV,
     * Servicios, Visitas, Especiales y Apoyo Alza. Todos terminan igual —
     * un proveedor, un concepto y un monto —, así que viven en una sola
     * tabla con el proceso como tipo. Lo propio de cada uno (los días
     * marcados de una ruta, el porcentaje de un apoyo) queda en `dias`
     * y en `datos`, tal como vino en el archivo.
     */
    public function up(): void
    {
        Schema::create('courier_pagos_proceso', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            /* Ruta CV | Servicios | Visitas | Especiales | Apoyo Alza */
            $table->string('proceso', 20);

            $table->unsignedInteger('fila_origen')->nullable();

            /* El proveedor como viene en el archivo, y el del catálogo que calza por RUT. */
            $table->string('proveedor', 255)->nullable();
            $table->string('rut_proveedor', 25)->nullable();
            $table->foreignId('courier_proveedor_id')
                ->nullable()
                ->constrained('courier_proveedores')
                ->nullOnDelete();

            $table->string('zona', 20)->nullable();
            $table->string('concepto', 255)->nullable();

            /* Valor por día (rutas y visitas) y días u otra cantidad que se pagan. */
            $table->unsignedBigInteger('valor_unitario')->nullable();
            $table->unsignedInteger('cantidad')->nullable();
            $table->unsignedBigInteger('total')->default(0);

            /* Días del mes marcados (rutas y visitas). */
            $table->json('dias')->nullable();

            /* El resto de la fila original. */
            $table->json('datos')->nullable();

            $table->string('archivo_origen', 255)->nullable();

            $table->timestamps();

            $table->index(['courier_periodo_id', 'proceso'], 'courier_pagos_proceso_periodo_proceso_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_pagos_proceso');
    }
};
