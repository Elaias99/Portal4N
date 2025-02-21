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
            $table->timestamp('fecha_respuesta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            //
            $table->dropColumn('fecha_respuesta');
        });
    }
};
