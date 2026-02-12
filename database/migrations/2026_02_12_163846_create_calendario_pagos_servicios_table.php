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
        Schema::create('calendario_pagos_servicios', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('anio');
            $table->unsignedTinyInteger('mes');

            $table->string('servicio');
            $table->unsignedInteger('creditos')->nullable();

            $table->date('fecha_pago');


            $table->timestamps();


            // Índice único para evitar duplicados por período + tipo
            $table->unique(
                ['anio', 'mes', 'servicio', 'creditos'],
                'calendario_pago_unique'
            );

            // Índice para búsquedas rápidas por período
            $table->index(['anio', 'mes']);


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendario_pagos_servicios');
    }
};
