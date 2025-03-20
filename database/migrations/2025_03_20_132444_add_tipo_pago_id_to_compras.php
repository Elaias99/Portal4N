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
        Schema::table('compras', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('tipo_pago_id')->nullable()->after('forma_pago'); // Nueva columna FK
            $table->foreign('tipo_pago_id')->references('id')->on('tipo_pagos')->onDelete('set null');
            $table->dropColumn('tipo_documento'); // Eliminamos la antigua columna de texto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            //
            $table->string('tipo_documento')->nullable(); // Volvemos a la versión anterior si hay rollback
            $table->dropForeign(['tipo_pago_id']);
            $table->dropColumn('tipo_pago_id');
        });
    }
};
