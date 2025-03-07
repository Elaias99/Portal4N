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
        Schema::create('reclamos', function (Blueprint $table) {
            $table->id(); // ID único del reclamo
            $table->foreignId('id_bulto')->constrained('bultos')->onDelete('cascade'); // Relación con bultos
            $table->foreignId('id_trabajador')->constrained('trabajadors')->onDelete('cascade'); // Relación con trabajador
            $table->text('descripcion'); // Detalles del reclamo
            $table->enum('estado', ['pendiente', 'en_revision', 'resuelto', 'rechazado'])->default('pendiente'); // Estado del reclamo
            $table->text('respuesta_admin')->nullable(); // Respuesta del administrador (opcional)
            $table->timestamps(); // Created_at y Updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamos');
    }
};
