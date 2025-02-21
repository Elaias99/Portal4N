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
        Schema::create('trabajadors', function (Blueprint $table) {
            $table->id();
            $table->string('Rut');
            $table->string('Nombre');
            // Fecha de inicio trabajo (columna) ///// LISTO///////////
            // Turno (tabla)/////LISTO////
            // Sistema de trabajo (tabla) //////LISTO///
            
            // Sucursales(tabla fk empresa)
            
            // Dirección personal (columna-> Calle número comuna) ///// LISTO ///////
            // Contácto de emergencia (nombre parentezco número) ///// LISTO ///////
            // Teléfono celular /// LISTO ///


            $table->string('SegundoNombre')->nullable();
            $table->string('TercerNombre')->nullable();
            $table->string('ApellidoPaterno');
            $table->string('ApellidoMaterno');
            $table->date('FechaNacimiento');
            // $table->string('Correo');
            $table->string('Foto')->nullable();
            $table->string('Casino');
            $table->string('ContratoFirmado');
            $table->string('AnexoContrato'); //Convertir a tabla
            $table->timestamps();

            ///// FK /////////////
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cargo_id');
            $table->unsignedBigInteger('situacion_id');
            $table->unsignedBigInteger('estado_civil_id');
            $table->unsignedBigInteger('comuna_id');
            $table->unsignedBigInteger('afp_id');
            $table->unsignedBigInteger('salud_id');

            
            // Add foreign key constraints
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('cargo_id')->references('id')->on('cargos')->onDelete('cascade');
            $table->foreign('situacion_id')->references('id')->on('situacions')->onDelete('cascade');
            $table->foreign('estado_civil_id')->references('id')->on('estado_civils')->onDelete('cascade');
            $table->foreign('comuna_id')->references('id')->on('comunas')->onDelete('cascade');
            $table->foreign('afp_id')->references('id')->on('a_f_p_s')->onDelete('cascade');
            $table->foreign('salud_id')->references('id')->on('saluds')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajadors');
    }
};
