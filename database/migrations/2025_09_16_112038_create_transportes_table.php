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
            $table->string('nombre');       // Ej: "Camión"
            $table->string('perfil_api');   // Ej: "driving-hgv"
            $table->timestamps();
        });

        // Relación con cotizadors: un cotizador usa 1 transporte
        Schema::table('cotizadors', function (Blueprint $table) {
            $table->foreignId('transporte_id')
                ->nullable() // por si quieres que sea opcional
                ->constrained('transportes')
                ->onDelete('set null');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizadors', function (Blueprint $table) {
            $table->dropForeign(['transporte_id']);
            $table->dropColumn('transporte_id');
        });

        Schema::dropIfExists('transportes');
    }
};
