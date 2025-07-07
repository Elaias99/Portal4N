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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();

                $table->foreignId('trabajador_id')->constrained()->onDelete('cascade');
                $table->enum('tipo', ['Contrato', 'Anexo']);
                $table->enum('estado', ['Firmado', 'Pendiente', 'Rechazado'])->default('Pendiente');

                $table->date('fecha_emision')->nullable();
                $table->date('fecha_firma')->nullable();

                $table->string('archivo')->nullable(); // Ruta al archivo PDF
                $table->string('firmado_por')->nullable(); // RRHH, nombre u otro dato útil


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
