<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Las 17 tablas tarifarias (0–16) son un catálogo único, no mensual.
     */
    public function up(): void
    {
        Schema::table('courier_tarifas', function (Blueprint $table) {
            $table->dropForeign(['courier_periodo_id']);
            $table->dropUnique('courier_tarifas_periodo_numero_unique');
            $table->dropColumn('courier_periodo_id');

            $table->unique('numero', 'courier_tarifas_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::table('courier_tarifas', function (Blueprint $table) {
            $table->dropUnique('courier_tarifas_numero_unique');

            $table->foreignId('courier_periodo_id')
                ->nullable()
                ->after('id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            $table->unique(['courier_periodo_id', 'numero'], 'courier_tarifas_periodo_numero_unique');
        });
    }
};