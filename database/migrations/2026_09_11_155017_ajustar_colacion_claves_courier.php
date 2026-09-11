<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Las claves de búsqueda de los catálogos deben comparar como el
     * BUSCARV de Excel: insensible a mayúsculas (se guardan en minúsculas)
     * pero SENSIBLE a tildes, ñ y espacios.
     *
     * Con utf8mb4_unicode_ci MySQL considera iguales "maipu" y "maipú",
     * lo que colapsó 68 de las 590 localidades en la primera carga.
     */
    public function up(): void
    {
        Schema::table('courier_cobertura_comunas', function (Blueprint $table) {
            $table->string('localidad_clave', 255)->collation('utf8mb4_bin')->change();
        });

        Schema::table('courier_configuracions', function (Blueprint $table) {
            $table->string('llave', 600)->collation('utf8mb4_bin')->change();
        });

        Schema::table('courier_proveedores', function (Blueprint $table) {
            $table->string('llave', 200)->collation('utf8mb4_bin')->change();
        });
    }

    public function down(): void
    {
        Schema::table('courier_cobertura_comunas', function (Blueprint $table) {
            $table->string('localidad_clave', 255)->collation('utf8mb4_unicode_ci')->change();
        });

        Schema::table('courier_configuracions', function (Blueprint $table) {
            $table->string('llave', 600)->collation('utf8mb4_unicode_ci')->change();
        });

        Schema::table('courier_proveedores', function (Blueprint $table) {
            $table->string('llave', 200)->collation('utf8mb4_unicode_ci')->change();
        });
    }
};