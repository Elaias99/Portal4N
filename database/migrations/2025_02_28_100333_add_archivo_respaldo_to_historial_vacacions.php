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
        Schema::table('historial_vacacions', function (Blueprint $table) {
            //
            $table->string('archivo_respaldo')->nullable()->after('tipo_dia'); // Agregar campo después de tipo_dia
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_vacacions', function (Blueprint $table) {
            //
            $table->dropColumn('archivo_respaldo');
        });
    }
};
