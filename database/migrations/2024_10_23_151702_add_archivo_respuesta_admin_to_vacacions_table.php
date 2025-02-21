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
        Schema::table('vacacions', function (Blueprint $table) {
            //
            $table->string('archivo_respuesta_admin')->nullable()->after('archivo_admin'); // Columna para el archivo adicional adjuntado por el administrador
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacacions', function (Blueprint $table) {
            //
            $table->dropColumn('archivo_respuesta_admin');
        });
    }
};
