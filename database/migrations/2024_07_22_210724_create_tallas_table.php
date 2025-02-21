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
        Schema::create('tallas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trabajador_id');
            $table->unsignedBigInteger('tipo_vestimenta_id');
            $table->string('talla');
            $table->timestamps();

            $table->foreign('trabajador_id')->references('id')->on('trabajadors')->onDelete('cascade');
            $table->foreign('tipo_vestimenta_id')->references('id')->on('tipo_vestimentas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tallas');
    }
};

