<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotizadors', function (Blueprint $table) {
            //
                        // Con o sin insumo (ej: 'con', 'sin')
            $table->enum('insumo', ['con', 'sin'])->nullable()->after('transporte_id');

            // Cantidad de unidades
            $table->integer('unidades')->nullable()->after('insumo');

            // Tipo de maquila (texto libre o luego lo desacoplamos)
            $table->string('tipo_maquila')->nullable()->after('unidades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizadors', function (Blueprint $table) {
            //
            $table->dropColumn(['insumo', 'unidades', 'tipo_maquila']);
        });
    }
};
