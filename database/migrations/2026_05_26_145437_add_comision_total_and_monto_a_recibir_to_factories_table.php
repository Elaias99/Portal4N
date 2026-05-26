<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->unsignedBigInteger('comision_total')
                ->nullable()
                ->after('diferencia_precio');

            $table->unsignedBigInteger('monto_a_recibir')
                ->nullable()
                ->after('comision_total');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropColumn([
                'comision_total',
                'monto_a_recibir',
            ]);
        });
    }
};