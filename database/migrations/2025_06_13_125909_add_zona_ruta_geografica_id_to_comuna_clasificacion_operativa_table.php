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
        Schema::table('comuna_clasificacion_operativa', function (Blueprint $table) {
            //
            $table->foreignId('zona_ruta_geografica_id')
                ->nullable()
                ->after('subzona_id') // O donde prefieras
                ->constrained('zona_ruta_geograficas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comuna_clasificacion_operativa', function (Blueprint $table) {
            //
            $table->dropForeign(['zona_ruta_geografica_id']);
            $table->dropColumn('zona_ruta_geografica_id');
        });
    }
};
