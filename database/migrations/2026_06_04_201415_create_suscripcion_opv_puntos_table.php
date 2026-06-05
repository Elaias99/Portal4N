<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_opv_puntos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('suscripcion_asignacion_id')
                ->constrained('suscripcion_asignaciones')
                ->restrictOnDelete();

            $table->string('ruta_nombre')->nullable();
            // Ej: Victor Perez, Manuel Hernandez, Marcelo Rubilar, Gerson Salazar

            $table->string('local', 50)->nullable();
            // Ej: 917, 671, 335

            $table->string('nombre_local')->nullable();
            // Ej: UNIMARC LOS TRAPENSES

            $table->string('nombre_local_corto')->nullable();
            // Ej: LOS TRAPENSES

            $table->string('direccion')->nullable();

            $table->string('comuna')->nullable();

            $table->timestamps();

            $table->index('suscripcion_asignacion_id', 'susc_opv_asig_idx');
            $table->index('ruta_nombre', 'susc_opv_ruta_idx');
            $table->index('local', 'susc_opv_local_idx');

            $table->unique(
                ['suscripcion_asignacion_id', 'local'],
                'susc_opv_asig_local_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_opv_puntos');
    }
};