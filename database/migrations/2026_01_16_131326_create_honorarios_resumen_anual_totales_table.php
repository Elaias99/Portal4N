<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('honorarios_resumen_anual_totales', function (Blueprint $table) {

            $table->id();

            // Identificación contribuyente
            $table->string('rut_contribuyente', 20);
            $table->string('razon_social');

            // Periodo
            $table->unsignedSmallInteger('anio');

            // Datos totales SII
            $table->unsignedInteger('folio_inicial')->nullable();
            $table->unsignedInteger('folio_final')->nullable();

            $table->unsignedInteger('boletas_vigentes')->default(0);
            $table->unsignedInteger('boletas_nulas')->default(0);

            $table->unsignedBigInteger('honorario_bruto')->default(0);
            $table->unsignedBigInteger('retenciones')->default(0);
            $table->unsignedBigInteger('total_liquido')->default(0);

            $table->timestamps();

            // Clave única lógica
            $table->unique(
                ['rut_contribuyente', 'anio'],
                'uq_honorarios_resumen_anual_totales'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honorarios_resumen_anual_totales');
    }
};
