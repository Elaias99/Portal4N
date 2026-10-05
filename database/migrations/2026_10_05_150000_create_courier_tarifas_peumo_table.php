<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Tarifas de Peumo (Comercial Peumo, servicio V. Trabajadores): por
     * localidad, lo que paga el primer bulto de una guía de despacho y lo
     * que paga cada uno de los demás. Se carga con la hoja Peumo de
     * Llaves_Courier.xlsx (courier:importar-llaves), que la reemplaza entera.
     *
     * Una localidad puede repetirse en el archivo; si sus valores no
     * coinciden, el cálculo no elige y deja la guía pendiente.
     */
    public function up(): void
    {
        Schema::create('courier_tarifas_peumo', function (Blueprint $table) {
            $table->id();
            $table->string('localidad', 255);
            $table->string('localidad_clave', 255)->collation('utf8mb4_bin')->index();
            $table->unsignedInteger('primer_bulto');
            $table->unsignedInteger('resto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tarifas_peumo');
    }
};
