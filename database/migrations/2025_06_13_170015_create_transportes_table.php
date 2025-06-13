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
        Schema::create('transportes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Agregar relación en zona_ruta_geograficas
        Schema::table('zona_ruta_geograficas', function (Blueprint $table) {
            $table->foreignId('transporte_id')
                  ->nullable()
                  ->after('tipo_zona_id')
                  ->constrained('transportes');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

         // Eliminar relación primero
        Schema::table('zona_ruta_geograficas', function (Blueprint $table) {
            $table->dropForeign(['transporte_id']);
            $table->dropColumn('transporte_id');
        });

        Schema::dropIfExists('transportes');
    }
};
