<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVacacionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vacacions', function (Blueprint $table) {
            $table->id();
            // Relación con el modelo Solicitud
            $table->unsignedBigInteger('solicitud_id');
            // Relación con el modelo Trabajador
            $table->unsignedBigInteger('trabajador_id');
            
            // Información específica de la vacación
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias');
            $table->timestamps();

            // Definir las claves foráneas
            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->onDelete('cascade');
            $table->foreign('trabajador_id')->references('id')->on('trabajadors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vacacions');
    }
}
