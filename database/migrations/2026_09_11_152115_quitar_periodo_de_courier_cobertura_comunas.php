<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * La cobertura deja de estar versionada por período: describe cómo
     * se asigna HOY una comuna a un agente. El período vive en los datos
     * mensuales, donde cada bulto queda grabado con lo que le tocó.
     */
    public function up(): void
    {
        Schema::table('courier_cobertura_comunas', function (Blueprint $table) {
            $table->dropForeign(['courier_periodo_id']);
            $table->dropUnique('courier_cobertura_periodo_localidad_unique');
            $table->dropIndex('courier_cobertura_periodo_agente_index');
            $table->dropColumn('courier_periodo_id');

            /*
             * Una localidad existe una sola vez en el catálogo.
             * localidad_clave = lower(localidad), sensible a tildes y espacios.
             */
            $table->unique('localidad_clave', 'courier_cobertura_localidad_unique');

            $table->boolean('activo')->default(true)->after('valor_retorno');
        });
    }

    public function down(): void
    {
        Schema::table('courier_cobertura_comunas', function (Blueprint $table) {
            $table->dropColumn('activo');
            $table->dropUnique('courier_cobertura_localidad_unique');

            $table->foreignId('courier_periodo_id')
                ->nullable()
                ->after('id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            $table->unique(['courier_periodo_id', 'localidad_clave'], 'courier_cobertura_periodo_localidad_unique');
            $table->index(['courier_periodo_id', 'courier_agente_id'], 'courier_cobertura_periodo_agente_index');
        });
    }
};