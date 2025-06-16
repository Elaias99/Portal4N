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
        Schema::table('zona_ruta_geograficas', function (Blueprint $table) {
            //

            $table->foreignId('origen_comuna_id')->nullable()->constrained('comunas')->nullOnDelete();
            $table->foreignId('destino_comuna_id')->nullable()->constrained('comunas')->nullOnDelete();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zona_ruta_geograficas', function (Blueprint $table) {
            //
            $table->dropForeign(['origen_comuna_id']);
            $table->dropForeign(['destino_comuna_id']);

            $table->dropColumn(['origen_comuna_id', 'destino_comuna_id']);
        });
    }
};
