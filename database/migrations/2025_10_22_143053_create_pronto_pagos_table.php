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
        Schema::create('pronto_pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('documento_financiero_id')
                  ->constrained('documentos_financieros')
                  ->onDelete('cascade');

            $table->date('fecha_pronto_pago')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pronto_pagos');
    }
};
