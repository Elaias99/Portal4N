<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_tarifas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            /*
             * Numero de tabla tarifaria, del 0 al 16.
             *
             * Es el valor que la configuracion de pago asigna a cada
             * combinacion de agente + comerciante + servicio.
             *
             * La tabla 0 vale $0 a cualquier peso, pero eso NO
             * significa "no pagar": el estado de pago es un dato
             * aparte. Hay 4 configuraciones marcadas para pagar que
             * apuntan a la tabla 0.
             */
            $table->unsignedTinyInteger('numero');

            /*
             * Nombre descriptivo de la tarifa.
             * Ejemplos: "STANDART REGIONES", "LANA RETAIL REGIONES".
             */
            $table->string('nombre', 255);

            /*
             * Monto que se suma por cada kilo sobre los 20.
             *
             * Cuando vale 0 la tarifa es plana: cobra lo mismo a
             * cualquier peso.
             */
            $table->integer('kilo_adicional')->default(0);

            $table->timestamps();

            /*
             * Una tabla no puede repetirse dentro del mismo periodo.
             */
            $table->unique(
                ['courier_periodo_id', 'numero'],
                'courier_tarifas_periodo_numero_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tarifas');
    }
};