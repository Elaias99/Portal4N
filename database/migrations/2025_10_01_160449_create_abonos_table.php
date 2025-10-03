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
        Schema::create('abonos', function (Blueprint $table) {
            $table->id();

            // Relación con Documento Financiero
            $table->foreignId('documento_financiero_id')
                  ->constrained('documentos_financieros')
                  ->onDelete('cascade');


            // Monto del abono en pesos chilenos (entero)
            $table->unsignedBigInteger('monto');

            // Fecha en que se hizo el abono
            $table->date('fecha_abono');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
