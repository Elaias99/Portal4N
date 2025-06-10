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
        Schema::create('frecuencia_dias', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('frecuencia_id');
            $table->string('dia_semana');


            $table->timestamps();

            $table->foreign('frecuencia_id')->references('id')->on('frecuencias_distribucion')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frecuencia_dias');
    }
};
