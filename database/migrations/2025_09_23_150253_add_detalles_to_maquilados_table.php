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
        Schema::table('maquilados', function (Blueprint $table) {
            //
            $table->string('duracion_proceso', 100)->nullable()->after('tipo_maquila_id');
            $table->boolean('requiere_transporte')->default(false)->after('duracion_proceso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maquilados', function (Blueprint $table) {
            //
            $table->dropColumn(['duracion_proceso', 'requiere_transporte']);
        });
    }
};
