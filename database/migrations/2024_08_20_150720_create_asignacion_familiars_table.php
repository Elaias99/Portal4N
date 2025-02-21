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
        Schema::create('asignacion_familiars', function (Blueprint $table) {
            $table->id();

            $table->string('tramo');  // Tramo de la asignación familiar (A, B, C, D)
            $table->decimal('salario_minimo', 15, 2);  // Salario mínimo para este tramo
            $table->decimal('salario_maximo', 15, 2);  // Salario máximo para este tramo
            $table->decimal('monto', 15, 2);  // Monto de la asignación familiar

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion_familiars');
    }
};
