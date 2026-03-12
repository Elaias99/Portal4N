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
        Schema::create('documento_compra_pagos_programados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('documento_compra_id')
                ->constrained('documentos_compras')
                ->onDelete('cascade');

            $table->date('fecha_programada');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->unique('documento_compra_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_compra_pagos_programados');
    }
};
