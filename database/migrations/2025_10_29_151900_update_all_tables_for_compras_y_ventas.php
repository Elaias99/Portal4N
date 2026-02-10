<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === ABONOS ===
        Schema::table('abonos', function (Blueprint $table) {
            if (Schema::hasColumn('abonos', 'documento_financiero_id')) {
                $table->foreignId('documento_financiero_id')
                      ->nullable()
                      ->change();
            }

            if (!Schema::hasColumn('abonos', 'documento_compra_id')) {
                $table->foreignId('documento_compra_id')
                      ->nullable()
                      ->constrained('documentos_compras')
                      ->onDelete('cascade')
                      ->after('documento_financiero_id');
            }
        });

        // === CRUCES ===
        Schema::table('cruces', function (Blueprint $table) {
            if (Schema::hasColumn('cruces', 'documento_financiero_id')) {
                $table->foreignId('documento_financiero_id')
                      ->nullable()
                      ->change();
            }

            if (!Schema::hasColumn('cruces', 'documento_compra_id')) {
                $table->foreignId('documento_compra_id')
                      ->nullable()
                      ->constrained('documentos_compras')
                      ->onDelete('cascade')
                      ->after('documento_financiero_id');
            }
        });

        // === PAGOS ===
        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'documento_financiero_id')) {
                $table->foreignId('documento_financiero_id')
                      ->nullable()
                      ->change();
            }

            if (!Schema::hasColumn('pagos', 'documento_compra_id')) {
                $table->foreignId('documento_compra_id')
                      ->nullable()
                      ->constrained('documentos_compras')
                      ->onDelete('cascade')
                      ->after('documento_financiero_id');
            }
        });

        // === PRONTO PAGOS ===
        Schema::table('pronto_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pronto_pagos', 'documento_financiero_id')) {
                $table->foreignId('documento_financiero_id')
                      ->nullable()
                      ->change();
            }

            if (!Schema::hasColumn('pronto_pagos', 'documento_compra_id')) {
                $table->foreignId('documento_compra_id')
                      ->nullable()
                      ->constrained('documentos_compras')
                      ->onDelete('cascade')
                      ->after('documento_financiero_id');
            }
        });
    }

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
