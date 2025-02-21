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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trabajador_id')->constrained('trabajadors')->onDelete('cascade');
            $table->string('campo');  // El campo que el empleado desea modificar
            $table->text('descripcion');  // Descripción del cambio solicitado
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');  // Estado de la solicitud

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
