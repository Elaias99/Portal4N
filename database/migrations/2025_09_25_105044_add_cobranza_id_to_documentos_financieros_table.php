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
        Schema::table('documentos_financieros', function (Blueprint $table) {

            // Agregamos la columna cobranza_id
            $table->unsignedBigInteger('cobranza_id')->nullable()->after('id');

            // Creamos la clave foránea
            $table->foreign('cobranza_id')
                  ->references('id')
                  ->on('cobranzas')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_financieros', function (Blueprint $table) {
            //
            $table->dropForeign(['cobranza_id']);
            $table->dropColumn('cobranza_id');
        });
    }
};
