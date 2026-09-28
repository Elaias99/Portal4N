<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Las hojas del mes que sacan bultos del pago: especiales,
     * Retornos, Blue y PagadosMesAnterior. Todas hacen lo mismo —
     * marcan un bulto — así que viven en una sola tabla con un tipo,
     * en vez de cuatro tablas iguales.
     */
    public function up(): void
    {
        Schema::create('courier_controles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            /*
             * especial              → hoja especiales (pago especial autorizado)
             * retorno               → hoja Retornos (se paga por comuna, no por tabla)
             * blue                  → hoja Blue (lo envió Blue Express)
             * pagado_mes_anterior   → estuvo en la nómina del mes pasado
             */
            $table->string('tipo', 30);

            /* Código del bulto con sufijo, como courier_bultos.seguimiento. */
            $table->string('seguimiento', 30);

            /*
             * Sólo lo usan los retornos, que traen su propio monto por
             * comuna en vez de salir de la matriz de pesos.
             */
            $table->integer('valor')->nullable();

            $table->string('archivo_origen', 150)->nullable();

            $table->timestamps();

            $table->unique(
                ['courier_periodo_id', 'tipo', 'seguimiento'],
                'courier_controles_periodo_tipo_seguimiento_unique'
            );

            $table->index('seguimiento', 'courier_controles_seguimiento_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_controles');
    }
};
