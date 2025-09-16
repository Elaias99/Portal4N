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
        Schema::create('cotizadors', function (Blueprint $table) {
            $table->id();

            $table->string('nombre_cliente');
            $table->enum('servicio', ['Transporte', 'Courier', 'Almacenaje'])->default('Transporte');
            $table->string('Origen');
            $table->string('Destino');
            // $table->string('');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizadors');
    }
};
