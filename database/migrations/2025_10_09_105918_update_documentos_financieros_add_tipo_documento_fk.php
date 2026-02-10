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
            // Eliminamos el campo antiguo
            if (Schema::hasColumn('documentos_financieros', 'tipo_doc')) {
                $table->dropColumn('tipo_doc');
            }

            // Agregamos la nueva relación con tipo_documentos
            $table->unsignedBigInteger('tipo_documento_id')->after('nro')->nullable();

            // Establecemos la clave foránea
            $table->foreign('tipo_documento_id')
                ->references('id')
                ->on('tipo_documentos')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_financieros', function (Blueprint $table) {
            // Revertir cambios
            if (Schema::hasColumn('documentos_financieros', 'tipo_documento_id')) {
                $table->dropForeign(['tipo_documento_id']);
                $table->dropColumn('tipo_documento_id');
            }

            // Volver a crear el campo anterior si fuera necesario
            $table->integer('tipo_doc')->nullable();
        });
    }
};
