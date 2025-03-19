<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('tipo_pago_id')->nullable()->after('tipo_cuenta_id');
            $table->foreign('tipo_pago_id')->references('id')->on('tipo_pagos')->onDelete('set null');

        });

        DB::statement("
            UPDATE proveedores 
            JOIN tipo_pagos ON proveedores.tipo_pago = tipo_pagos.nombre 
            SET proveedores.tipo_pago_id = tipo_pagos.id
        ");


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            //
            $table->dropForeign(['tipo_pago_id']);
            $table->dropColumn('tipo_pago_id');
        });
    }
};
