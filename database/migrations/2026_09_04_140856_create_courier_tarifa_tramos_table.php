<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_tarifa_tramos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_tarifa_id')
                ->constrained('courier_tarifas')
                ->cascadeOnDelete();

            /*
             * Peso en kilos enteros, del 1 al 20.
             *
             * La planilla busca por coincidencia exacta del peso, no
             * por rango, asi que cada kilo tiene su propio valor.
             *
             * Solo se guardan los primeros 20 kilos porque de ahi en
             * adelante la matriz es una progresion lineal:
             *   valor = valor_20kg + (peso - 20) * kilo_adicional
             *
             * Verificado contra las 84.677 celdas de la matriz
             * original (hasta 5.001 kg): cero diferencias.
             */
            $table->unsignedSmallInteger('peso');

            /*
             * Monto en pesos para ese peso exacto.
             */
            $table->integer('valor');

            /*
             * No lleva timestamps: son datos de referencia que se
             * cargan junto con la tarifa y no se editan por separado.
             */

            $table->unique(
                ['courier_tarifa_id', 'peso'],
                'courier_tarifa_tramos_tarifa_peso_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tarifa_tramos');
    }
};