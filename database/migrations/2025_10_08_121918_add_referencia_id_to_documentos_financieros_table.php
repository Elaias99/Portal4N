<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_financieros', function (Blueprint $table) {
            // Campo que referencia otro documento (autorreferencia)
            $table->unsignedBigInteger('referencia_id')->nullable()->after('folio');

            // Clave foránea a sí misma
            $table->foreign('referencia_id')
                  ->references('id')
                  ->on('documentos_financieros')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_financieros', function (Blueprint $table) {
            $table->dropForeign(['referencia_id']);
            $table->dropColumn('referencia_id');
        });
    }
};
