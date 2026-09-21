<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_pesajes', function (Blueprint $table) {
            $table->id();

            /* Período bajo el que se cargó el archivo. */
            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            /*
             * Columna Codigo del CSV: código con sufijo, igual a
             * courier_bultos.seguimiento. Es la que cruza con Geolice.
             */
            $table->string('seguimiento', 30);

            /* Columna Cod_seguimiento: el mismo código sin sufijo. */
            $table->string('codigo', 20);

            /*
             * El CSV no trae fecha: sale del nombre del archivo
             * ("Proceso del dia 04-09-2026.csv"). Un mismo bulto puede
             * pesarse en días distintos, a veces con kilos distintos;
             * se guardan todos y el cálculo decide cuál manda.
             */
            $table->date('fecha_pesaje');

            /*
             * Columna Notas: kilos enteros de la balanza (PesoReal de la
             * planilla). Puede venir 0: pasó por la balanza sin peso.
             */
            $table->unsignedSmallInteger('kilos');

            $table->string('archivo_origen', 100)->nullable();

            $table->timestamps();

            /* Un bulto, un pesaje por día; recargar el mismo CSV actualiza. */
            $table->unique(
                ['seguimiento', 'fecha_pesaje'],
                'courier_pesajes_seguimiento_fecha_unique'
            );

            $table->index('seguimiento', 'courier_pesajes_seguimiento_index');

            $table->index(
                ['courier_periodo_id', 'fecha_pesaje'],
                'courier_pesajes_periodo_fecha_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_pesajes');
    }
};
