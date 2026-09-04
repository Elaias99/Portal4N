<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_periodos', function (Blueprint $table) {
            $table->id();

            /*
             * Identificador del período en formato AAAAMM.
             *
             * Ejemplos:
             * 202607 → julio 2026
             * 202608 → agosto 2026
             *
             * Coincide con el nombre de la plantilla que mantiene
             * Operaciones (202608_4N_COURIER_RESPALDOS.xlsx).
             */
            $table->char('codigo', 6);

            /*
             * Año y mes por separado para poder ordenar y filtrar
             * sin tener que parsear el código.
             */
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            /*
             * abierto  → el catálogo todavía puede recibir cambios.
             * cerrado  → el período ya se pagó y queda como respaldo
             *            histórico. No debe modificarse.
             */
            $table->enum('estado', ['abierto', 'cerrado'])
                ->default('abierto');

            /*
             * Nota libre para registrar el origen de la carga o
             * cualquier particularidad del período.
             */
            $table->string('observacion', 255)->nullable();

            $table->timestamps();

            /*
             * No pueden existir dos períodos con el mismo código.
             */
            $table->unique(
                'codigo',
                'courier_periodos_codigo_unique'
            );

            /*
             * Facilita el listado cronológico que usará el selector
             * de período en la vista.
             */
            $table->index(
                ['anio', 'mes'],
                'courier_periodos_anio_mes_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_periodos');
    }
};