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
        Schema::create('ordenes_transporte', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('comuna_id');
            $table->unsignedBigInteger('zona_ruta_geografica_id');
            $table->integer('orden')->nullable();


            $table->timestamps();

            $table->foreign('comuna_id')->references('id')->on('comunas')->onDelete('cascade');
            $table->foreign('zona_ruta_geografica_id')->references('id')->on('zona_ruta_geograficas')->onDelete('cascade');

            $table->unique(['comuna_id', 'zona_ruta_geografica_id'], 'comuna_ruta_unique');



        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_transporte');
    }
};
