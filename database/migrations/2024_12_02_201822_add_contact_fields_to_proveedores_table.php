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
            $table->string('nombre_contacto')->nullable();  // Nombre del contacto (persona)
            $table->string('rut_contacto')->nullable();     // RUT del contacto (persona)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('nombre_contacto');
            $table->dropColumn('rut_contacto');
        });
    }
};
