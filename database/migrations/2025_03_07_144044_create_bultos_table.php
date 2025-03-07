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
        Schema::create('bultos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo_bulto')->unique(); // Código identificador del bulto
            $table->text('direccion'); // Dirección de destino
            $table->string('comuna', 100); // Comuna de destino
            $table->timestamp('fecha_carga')->useCurrent(); // Fecha en que se creó el bulto
            $table->enum('estado', ['pendiente', 'en_transito', 'entregado'])->default('pendiente'); // Estado del bulto


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bultos');
    }
};
