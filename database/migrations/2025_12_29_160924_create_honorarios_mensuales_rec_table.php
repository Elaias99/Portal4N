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
        Schema::create('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->id();

            // Identificación del contribuyente (receptor)
            $table->string('rut_contribuyente', 20);
            $table->string('razon_social');

            // Período del informe
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes'); // 1–12

            // Datos de la boleta
            $table->unsignedInteger('folio');
            $table->date('fecha_emision');
            $table->string('estado', 20); // VIGENTE / ANULADA
            $table->date('fecha_anulacion')->nullable();

            // Emisor
            $table->string('rut_emisor', 20);
            $table->string('razon_social_emisor');
            $table->boolean('sociedad_profesional')->default(false);

            // Montos
            $table->unsignedBigInteger('monto_bruto')->default(0);
            $table->unsignedBigInteger('monto_retenido')->default(0);
            $table->unsignedBigInteger('monto_pagado')->default(0);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('honorarios_mensuales_rec');
    }
};
