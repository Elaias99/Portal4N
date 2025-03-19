<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Asegurar nombres únicos
            $table->timestamps();
        });

        // Insertar los tipos de pago iniciales
        DB::table('tipo_pagos')->insert([
            ['nombre' => 'FACTURA'],
            ['nombre' => 'BOLETA'],
            ['nombre' => 'TRANSFERENCIA'],
            ['nombre' => 'EFECTIVO'],
            ['nombre' => 'FACTURA EXENTA'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_pagos');
    }
};
