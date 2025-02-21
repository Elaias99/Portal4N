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
        Schema::create('historial_vacacions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trabajador_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias_laborales');
            $table->string('tipo_dia'); // Ej: 'vacaciones', 'administrativo', etc.
            $table->text('comentario_admin')->nullable();
            $table->boolean('es_historico')->default(true); // Todas las entradas aquí serán históricas por defecto
            $table->timestamps();

            // Llave foránea para relacionar con la tabla de trabajadores
            $table->foreign('trabajador_id')->references('id')->on('trabajadors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_vacacions');
    }
};
