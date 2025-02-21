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
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            $table->string('numero_celular', 20)->nullable();  // Añadir columna para número de celular
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            $table->dropColumn('numero_celular');  // Eliminar la columna si se revierte la migración
        });
    }
};
