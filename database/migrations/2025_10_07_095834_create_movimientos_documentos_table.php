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
        Schema::create('movimientos_documentos', function (Blueprint $table) {
            $table->id();


            // Documento asociado (relación con documentos_financieros)
            $table->foreignId('documento_financiero_id')
                  ->nullable()
                  ->constrained('documentos_financieros')
                  ->onDelete('cascade');

            // Usuario que realizó la acción
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // Tipo de acción realizada
            $table->string('tipo_movimiento', 100); // Ej: 'Importación', 'Actualización', 'Abono registrado'

            // Descripción textual de la acción
            $table->text('descripcion')->nullable();

            // Estados previos y nuevos (JSON opcionales)
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_documentos');
    }
};
