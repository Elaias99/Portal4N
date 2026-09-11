<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Agente + comerciante + servicio → pagar / tabla, como catálogo único.
     */
    public function up(): void
    {
        Schema::table('courier_configuracions', function (Blueprint $table) {
            $table->dropForeign(['courier_periodo_id']);
            $table->dropUnique('courier_configuraciones_periodo_llave_unique');
            $table->dropIndex('courier_configuraciones_periodo_agente_index');
            $table->dropColumn('courier_periodo_id');

            $table->unique('llave', 'courier_configuraciones_llave_unique');

            $table->boolean('activo')->default(true)->after('tabla');
        });
    }

    public function down(): void
    {
        Schema::table('courier_configuracions', function (Blueprint $table) {
            $table->dropColumn('activo');
            $table->dropUnique('courier_configuraciones_llave_unique');

            $table->foreignId('courier_periodo_id')
                ->nullable()
                ->after('id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            $table->unique(['courier_periodo_id', 'llave'], 'courier_configuraciones_periodo_llave_unique');
            $table->index(['courier_periodo_id', 'courier_agente_id'], 'courier_configuraciones_periodo_agente_index');
        });
    }
};