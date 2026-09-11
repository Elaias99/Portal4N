<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_pesos_transformados', function (Blueprint $table) {
            $table->id();

            /*
             * Peso declarado exactamente como lo escribe Geolice:
             * "2.48 kg", "18.22 kg", "X". Se busca por igualdad de texto,
             * igual que el BUSCARV de la planilla.
             */
            $table->string('texto', 50)->unique();

            /*
             * Entero que usa la matriz de tarifas.
             * Regla observada: truncar, mínimo 1. "X" → 1.
             */
            $table->unsignedSmallInteger('peso');

            /*
             * catalogo: vino de la hoja PesoTransformado.
             * regla:    lo generó el sistema al encontrar un texto nuevo
             *           (equivale a ControlPesosPendientes de la planilla).
             */
            $table->enum('origen', ['catalogo', 'regla'])->default('catalogo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_pesos_transformados');
    }
};