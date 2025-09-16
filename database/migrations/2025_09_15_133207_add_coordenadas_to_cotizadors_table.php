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
            //
            $table->decimal('origen_lat', 10, 7)->nullable()->after('Origen');
            $table->decimal('origen_lon', 10, 7)->nullable()->after('origen_lat');
            $table->decimal('destino_lat', 10, 7)->nullable()->after('Destino');
            $table->decimal('destino_lon', 10, 7)->nullable()->after('destino_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizadors', function (Blueprint $table) {
            //
            $table->dropColumn(['origen_lat', 'origen_lon', 'destino_lat', 'destino_lon']);
        });
    }
};
