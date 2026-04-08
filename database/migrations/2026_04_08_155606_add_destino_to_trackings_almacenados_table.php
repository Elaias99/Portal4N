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
        Schema::table('trackings_almacenados', function (Blueprint $table) {
            $table->string('destino', 50)->after('codigo_tracking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trackings_almacenados', function (Blueprint $table) {
            $table->dropColumn('destino');
        });
    }
};
