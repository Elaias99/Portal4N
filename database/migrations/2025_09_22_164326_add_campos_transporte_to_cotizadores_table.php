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
        Schema::table('cotizadors', function (Blueprint $table) {
            // Lleva pioneta: 0 = no, 1 = sí (y a futuro podría ser 2, 3, etc.)
            $table->tinyInteger('lleva_pioneta')->default(0)->after('distancia_km');

            // Con o sin carga: valores iniciales "con", "sin"
            $table->string('con_carga', 20)->nullable()->after('lleva_pioneta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizadors', function (Blueprint $table) {
            //
            $table->dropColumn(['lleva_pioneta', 'con_carga']);
        });
    }
};
