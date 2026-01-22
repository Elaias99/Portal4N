<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) ABONOS
        Schema::table('abonos', function (Blueprint $table) {
            $table->unsignedBigInteger('honorario_mensual_rec_id')->nullable()->after('documento_compra_id');

            $table->foreign('honorario_mensual_rec_id', 'abonos_honorario_rec_fk')
                ->references('id')
                ->on('honorarios_mensuales_rec')
                ->nullOnDelete();
        });

        // 2) CRUCES
        Schema::table('cruces', function (Blueprint $table) {
            $table->unsignedBigInteger('honorario_mensual_rec_id')->nullable()->after('documento_compra_id');

            $table->foreign('honorario_mensual_rec_id', 'cruces_honorario_rec_fk')
                ->references('id')
                ->on('honorarios_mensuales_rec')
                ->nullOnDelete();
        });

        // 3) PAGOS
        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('honorario_mensual_rec_id')->nullable()->after('documento_compra_id');

            $table->foreign('honorario_mensual_rec_id', 'pagos_honorario_rec_fk')
                ->references('id')
                ->on('honorarios_mensuales_rec')
                ->nullOnDelete();
        });

        // 4) PRONTO_PAGOS
        Schema::table('pronto_pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('honorario_mensual_rec_id')->nullable()->after('documento_compra_id');

            $table->foreign('honorario_mensual_rec_id', 'pronto_pagos_honorario_rec_fk')
                ->references('id')
                ->on('honorarios_mensuales_rec')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropForeign('abonos_honorario_rec_fk');
            $table->dropColumn('honorario_mensual_rec_id');
        });

        Schema::table('cruces', function (Blueprint $table) {
            $table->dropForeign('cruces_honorario_rec_fk');
            $table->dropColumn('honorario_mensual_rec_id');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign('pagos_honorario_rec_fk');
            $table->dropColumn('honorario_mensual_rec_id');
        });

        Schema::table('pronto_pagos', function (Blueprint $table) {
            $table->dropForeign('pronto_pagos_honorario_rec_fk');
            $table->dropColumn('honorario_mensual_rec_id');
        });
    }
};
