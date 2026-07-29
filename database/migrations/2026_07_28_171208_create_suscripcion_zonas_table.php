<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_zonas', function (Blueprint $table) {
            $table->id();

            /*
             * Número visible y operativo de la zona.
             *
             * Ejemplos:
             * 1  → Vitacura
             * 2  → Lo Barnechea
             * 17 → Plaza Oeste 4
             */
            $table->unsignedSmallInteger('numero_zona');

            /*
             * Nombre del despacho asociado a la zona.
             */
            $table->string('despacho', 150);

            /*
             * Permite deshabilitar una zona sin eliminarla
             * ni perder sus relaciones históricas.
             */
            $table->boolean('activo')->default(true);

            $table->timestamps();

            /*
             * No pueden existir dos registros con el mismo
             * número oficial de zona.
             */
            $table->unique(
                'numero_zona',
                'suscripcion_zonas_numero_zona_unique'
            );

            /*
             * Facilita la consulta habitual de zonas activas.
             */
            $table->index(
                'activo',
                'suscripcion_zonas_activo_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_zonas');
    }
};