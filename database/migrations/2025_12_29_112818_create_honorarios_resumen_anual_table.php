<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('honorarios_resumen_anual', function (Blueprint $table) {
            $table->id();

            // Identificación contribuyente
            $table->string('rut_contribuyente', 20);
            $table->string('razon_social');

            // Período
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes'); // 1-12
            $table->string('mes_nombre', 15);

            // Folios
            $table->unsignedInteger('folio_inicial')->nullable();
            $table->unsignedInteger('folio_final')->nullable();

            // Cantidades
            $table->unsignedInteger('boletas_vigentes')->default(0);
            $table->unsignedInteger('boletas_nulas')->default(0);

            // Montos
            $table->unsignedBigInteger('honorario_bruto')->default(0);
            $table->unsignedBigInteger('retenciones')->default(0);
            $table->unsignedBigInteger('total_liquido')->default(0);

            // Auditoría básica
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honorarios_resumen_anual');
    }
};
