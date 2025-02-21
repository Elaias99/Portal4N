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
        Schema::table('proveedores', function (Blueprint $table) {
            //
            $table->string('telefono_empresa');
            $table->string('direccion_empresa');


            $table->string('Nombre_RepresentanteLegal');
            $table->string('Rut_RepresentanteLegal');
            $table->string('Telefono_RepresentanteLegal');
            $table->string('Correo_RepresentanteLegal');

            // Campos para el contacto de la empresa
            $table->string('contacto_nombre');
            $table->string('contacto_telefono');
            $table->string('contacto_correo');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'telefono_empresa',
                'direccion_empresa',
                'Nombre_RepresentanteLegal',
                'Rut_RepresentanteLegal',
                'Telefono_RepresentanteLegal',
                'Correo_RepresentanteLegal',
                'contacto_nombre',
                'contacto_telefono',
                'contacto_correo',
            ]);
        });
        
    }
};
