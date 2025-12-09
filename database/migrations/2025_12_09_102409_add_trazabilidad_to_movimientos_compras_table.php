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
        Schema::table('movimientos_compras', function (Blueprint $table) {
            // Nuevos campos para trazabilidad extendida
            $table->string('tipo_movimiento', 100)->nullable()->after('fecha_cambio');
            $table->text('descripcion')->nullable()->after('tipo_movimiento');
            $table->longText('datos_anteriores')->nullable()->after('descripcion');
            $table->longText('datos_nuevos')->nullable()->after('datos_anteriores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_compras', function (Blueprint $table) {
            //
            $table->dropColumn(['tipo_movimiento', 'descripcion', 'datos_anteriores', 'datos_nuevos']);
        });
    }
};
