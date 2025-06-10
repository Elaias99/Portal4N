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
        Schema::create('frecuencias_distribucion', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('comuna_id');
            $table->unsignedBigInteger('proveedor_id');


            $table->timestamps();

            $table->foreign('comuna_id')->references('id')->on('comunas')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frecuencias_distribucion');
    }
};
