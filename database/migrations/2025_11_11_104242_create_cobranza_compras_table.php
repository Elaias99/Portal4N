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
        Schema::create('cobranza_compras', function (Blueprint $table) {
            $table->id();
            $table->string('rut_cliente')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('servicio')->nullable();
            $table->integer('creditos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobranza_compras');
    }
};
