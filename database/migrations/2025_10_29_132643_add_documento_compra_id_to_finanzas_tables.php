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

        // Tabla: abonos
        Schema::table('abonos', function (Blueprint $table) {
            $table->foreignId('documento_compra_id')
                  ->nullable()
                  ->after('documento_financiero_id')
                  ->constrained('documentos_compras')
                  ->cascadeOnDelete();
        });

        // Tabla: cruces
        Schema::table('cruces', function (Blueprint $table) {
            $table->foreignId('documento_compra_id')
                  ->nullable()
                  ->after('documento_financiero_id')
                  ->constrained('documentos_compras')
                  ->cascadeOnDelete();
        });

        // Tabla: pagos
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('documento_compra_id')
                  ->nullable()
                  ->after('documento_financiero_id')
                  ->constrained('documentos_compras')
                  ->cascadeOnDelete();
        });

        // Tabla: pronto_pagos
        Schema::table('pronto_pagos', function (Blueprint $table) {
            $table->foreignId('documento_compra_id')
                  ->nullable()
                  ->after('documento_financiero_id')
                  ->constrained('documentos_compras')
                  ->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropForeign(['documento_compra_id']);
            $table->dropColumn('documento_compra_id');
        });

        Schema::table('cruces', function (Blueprint $table) {
            $table->dropForeign(['documento_compra_id']);
            $table->dropColumn('documento_compra_id');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['documento_compra_id']);
            $table->dropColumn('documento_compra_id');
        });

        Schema::table('pronto_pagos', function (Blueprint $table) {
            $table->dropForeign(['documento_compra_id']);
            $table->dropColumn('documento_compra_id');
        });
    }
};
