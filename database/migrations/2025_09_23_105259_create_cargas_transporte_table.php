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
        Schema::create('cargas_transporte', function (Blueprint $table) {
            $table->id();

            // Relación con cotizadores
            $table->unsignedBigInteger('cotizador_id');
            $table->foreign('cotizador_id')->references('id')->on('cotizadors')->onDelete('cascade');

            // Datos de la carga
            $table->string('descripcion');            // Ej: Pallets de fruta
            $table->integer('cantidad');              // Ej: 10
            $table->string('medida', 50)->nullable(); // Ej: cajas, pallets, kg, toneladas, m³
            $table->decimal('peso_total', 10, 2)->nullable(); // Peso total (opcional)


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargas_transporte');
    }
};
