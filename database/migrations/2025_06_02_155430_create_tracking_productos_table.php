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
        Schema::create('tracking_productos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo'); // código escaneado del producto
            $table->string('estado'); // Ej: 'Retiro', 'Recepcionado'
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('trabajador_id')->nullable(); // para trazabilidad real
            $table->unsignedBigInteger('area_id')->nullable();       // para saber de qué área se hizo


            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('trabajador_id')->references('id')->on('trabajadors')->onDelete('set null');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('set null');



        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_productos');
    }
};
