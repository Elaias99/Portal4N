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
        Schema::table('solicitudes', function (Blueprint $table) {
            //
            $table->string('archivo_admin')->nullable(); // Columna para el archivo adjunto por el administrador
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            //
            $table->dropColumn('archivo_admin');
        });
    }
};
