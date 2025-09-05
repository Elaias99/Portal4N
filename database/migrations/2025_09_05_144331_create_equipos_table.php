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
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            
            $table->string('tipo'); // PC, Notebook, Impresora, Tablet...
            $table->string('marca');
            $table->string('modelo');
            $table->string('procesador')->nullable();
            $table->string('ram')->nullable();
            $table->string('version_windows')->nullable();
            $table->string('nombre_equipo')->nullable();
            $table->string('direccion_ip')->nullable();
            $table->string('controlador')->nullable();
            $table->string('tipo_impresora')->nullable();
            $table->string('resolucion')->nullable();
            $table->string('tamano_etiqueta')->nullable();
            $table->text('funcion_principal')->nullable();
            $table->string('ubicacion');
            $table->string('usuario')->nullable();
            $table->string('usuario_asignado')->nullable();
            $table->string('contrasena')->nullable();
            $table->string('estado')->default('activo');
            $table->text('observacion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
