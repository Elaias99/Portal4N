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
        Schema::create('desvinculaciones', function (Blueprint $table) {
            $table->id(); // bigint, PK
            $table->foreignId('trabajador_id')->constrained('trabajadors')->onDelete('cascade');
            $table->foreignId('situacion_id')->constrained('situacions')->onDelete('restrict');
            $table->foreignId('sistema_trabajo_id')->constrained('sistema_trabajos')->onDelete('restrict');
            $table->date('fecha_desvinculo'); // se asignará en el controlador/modelo
            $table->text('motivo')->nullable(); // más adelante se puede cambiar a FK
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desvinculaciones');
    }
};
