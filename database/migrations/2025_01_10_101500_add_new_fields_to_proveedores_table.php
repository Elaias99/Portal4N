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

            $table->string('giro_comercial')->nullable();
            $table->string('direccion_facturacion')->nullable();
            $table->string('direccion_despacho')->nullable();
            $table->string('comuna_empresa')->nullable();
            $table->string('nombre_contacto2')->nullable();
            $table->string('telefono_contacto2')->nullable();
            $table->string('correo_contacto2')->nullable();
            $table->string('correo_banco')->nullable();
            $table->string('nombre_razon_social_banco')->nullable();
            $table->string('rut_banco')->nullable();

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {

            $table->dropColumn([
                'giro_comercial',
                'direccion_facturacion',
                'direccion_despacho',
                'comuna_empresa',
                'nombre_contacto2',
                'telefono_contacto2',
                'correo_contacto2',
                'correo_banco',
                'nombre_razon_social_banco',
                'rut_banco',
            ]);
            
        });
    }
};
