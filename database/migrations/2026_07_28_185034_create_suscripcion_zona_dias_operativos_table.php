<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_zona_dias_operativos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('suscripcion_zona_id')
                ->constrained('suscripcion_zonas')
                ->restrictOnDelete();

            /*
             * Fecha específica del calendario mensual.
             *
             * Ejemplo:
             * 2026-06-20
             */
            $table->date('fecha');

            /*
             * true  = la zona tuvo despacho ese día.
             * false = la zona completa no tuvo despacho ese día.
             */
            $table->boolean('hubo_despacho')->default(true);

            /*
             * Comentario opcional sobre la suspensión o modificación
             * del despacho de la zona.
             */
            $table->string('observacion', 500)->nullable();

            $table->timestamps();

            /*
             * Una zona solo puede tener un registro para una fecha.
             */
            $table->unique(
                [
                    'suscripcion_zona_id',
                    'fecha',
                ],
                'suscripcion_zona_dias_operativos_zona_fecha_unique'
            );

            /*
             * Facilita la búsqueda de todos los registros de un período.
             */
            $table->index(
                'fecha',
                'suscripcion_zona_dias_operativos_fecha_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_zona_dias_operativos');
    }
};