<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_importaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            /*
             * Qué se cargó: geolice (descarga de bultos) hoy; pesajes
             * (CSV de bodega) cuando llegue ese paso.
             */
            $table->string('tipo', 20);

            /* Nombre original del archivo que subió el usuario. */
            $table->string('archivo', 150);

            /*
             * Quién hizo la carga. Es lo que la planilla no registra.
             * Nullable por si la carga viene del comando de terminal.
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /* Conteos básicos, repetidos fuera del JSON para listar rápido. */
            $table->unsignedInteger('filas')->default(0);
            $table->unsignedInteger('nuevos')->default(0);
            $table->unsignedInteger('actualizados')->default(0);
            $table->unsignedInteger('duracion_seg')->nullable();

            /*
             * Resumen completo de la carga tal como lo produjo la clase de
             * importación: conteos, estados, comunas fuera de catálogo,
             * combinaciones sin configuración. Es una foto del momento;
             * las alertas vivas se recalculan desde courier_bultos.
             */
            $table->json('resumen');

            $table->timestamps();

            $table->index(
                ['courier_periodo_id', 'created_at'],
                'courier_importaciones_periodo_fecha_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_importaciones');
    }
};
