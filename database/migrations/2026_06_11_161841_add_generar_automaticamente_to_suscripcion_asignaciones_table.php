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
        Schema::table('suscripcion_asignaciones', function (Blueprint $table) {
            $table->boolean('generar_automaticamente')
                ->nullable()
                ->after('grupo_prefactura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suscripcion_asignaciones', function (Blueprint $table) {
            $table->dropColumn('generar_automaticamente');
        });
    }
};