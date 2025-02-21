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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social'); // Nombre del proveedor
            $table->string('rut')->unique(); // Identificador único
            $table->string('banco'); // Banco
            $table->string('tipo_cuenta'); // Tipo de cuenta
            $table->string('nro_cuenta'); // Número de cuenta
            $table->string('tipo_pago'); // Términos de pago

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
