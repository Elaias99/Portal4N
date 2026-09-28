<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_bultos', function (Blueprint $table) {
            /*
             * Variables o Lanas. Es la columna "tipo de Pago" de BaseCL:
             * no cambia el valor, pero es como Finanzas lee el resumen.
             */
            $table->string('tipo_pago', 20)->nullable()->after('zona');

            /*
             * A quién se le paga este bulto: agente + repartidor
             * resueltos contra courier_proveedores. Se guarda en el
             * bulto para que el resumen no tenga que rehacer el cruce
             * y para que quede registro de a quién se le asignó.
             */
            $table->foreignId('courier_proveedor_id')
                ->nullable()
                ->after('courier_configuracion_id')
                ->constrained('courier_proveedores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courier_bultos', function (Blueprint $table) {
            $table->dropForeign(['courier_proveedor_id']);
            $table->dropColumn(['tipo_pago', 'courier_proveedor_id']);
        });
    }
};
