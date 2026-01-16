<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('honorarios_mensuales_rec_totales', function (Blueprint $table) {

            $table->id();

            // Identificación contribuyente
            $table->string('rut_contribuyente', 20);
            $table->string('razon_social');

            // Periodo
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            // Totales oficiales SII (solo vigentes)
            $table->unsignedBigInteger('monto_bruto')->default(0);
            $table->unsignedBigInteger('monto_retenido')->default(0);
            $table->unsignedBigInteger('monto_pagado')->default(0);

            $table->timestamps();

            // Clave única lógica
            $table->unique(
                ['rut_contribuyente', 'anio', 'mes'],
                'uq_honorarios_mensuales_rec_totales'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honorarios_mensuales_rec_totales');
    }
};
