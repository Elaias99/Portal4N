<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {

            // Estado financiero automático (Al día / Vencido)
            $table->string('estado_financiero_inicial')
                  ->nullable()
                  ->after('estado');

            // Estado financiero manual (Abono, Pago, Cruce, etc.)
            $table->string('estado_financiero')
                  ->nullable()
                  ->after('estado_financiero_inicial');

            // Fecha del último cambio de estado financiero
            $table->date('fecha_estado_financiero')
                  ->nullable()
                  ->after('estado_financiero');

            // Saldo financiero interno
            $table->bigInteger('saldo_pendiente')
                  ->nullable()
                  ->after('monto_pagado');
        });
    }

    public function down(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->dropColumn([
                'estado_financiero_inicial',
                'estado_financiero',
                'fecha_estado_financiero',
                'saldo_pendiente',
            ]);
        });
    }
};
