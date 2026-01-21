<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->unsignedBigInteger('cobranza_compra_id')
                  ->nullable()
                  ->after('empresa_id');

            $table->foreign('cobranza_compra_id')
                  ->references('id')
                  ->on('cobranza_compras')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->dropForeign(['cobranza_compra_id']);
            $table->dropColumn('cobranza_compra_id');
        });
    }
};
