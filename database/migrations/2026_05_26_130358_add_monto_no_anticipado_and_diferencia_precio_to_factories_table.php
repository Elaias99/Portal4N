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
            $table->unsignedBigInteger('monto_no_anticipado')
                ->nullable()
                ->after('diferencia');

            $table->unsignedBigInteger('diferencia_precio')
                ->nullable()
                ->after('monto_no_anticipado');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropColumn([
                'monto_no_anticipado',
                'diferencia_precio',
            ]);
        });
    }
};