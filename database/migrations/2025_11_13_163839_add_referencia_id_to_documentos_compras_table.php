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
        Schema::table('documentos_compras', function (Blueprint $table) {
            $table->unsignedBigInteger('referencia_id')
                  ->nullable()
                  ->after('cobranza_compra_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_compras', function (Blueprint $table) {
            $table->dropColumn('referencia_id');
        });
    }
};
