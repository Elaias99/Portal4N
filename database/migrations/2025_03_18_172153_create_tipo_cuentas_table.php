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
        Schema::create('tipo_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Insertar los tipos de cuenta predeterminados
        DB::table('tipo_cuentas')->insert([
            ['nombre' => 'Cuenta Vista'],
            ['nombre' => 'Cuenta Corriente'],
            ['nombre' => 'Cuenta de Ahorro'],
            ['nombre' => 'Cuenta Rut'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_cuentas');
    }
};
