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
            $table->unsignedBigInteger('tipo_cuenta_id')->nullable()->after('banco_id');
            $table->foreign('tipo_cuenta_id')->references('id')->on('tipo_cuentas')->onDelete('set null');
        });

        // Corrección de nombres en tipo_cuenta antes de actualizar
        DB::statement("
            UPDATE proveedores SET tipo_cuenta = 'Cuenta de Ahorro' WHERE tipo_cuenta = 'Cuenta Ahorro';
        ");

        // Migrar datos de "tipo_cuenta" a "tipo_cuenta_id"
        DB::statement("
            UPDATE proveedores 
            JOIN tipo_cuentas ON proveedores.tipo_cuenta = tipo_cuentas.nombre 
            SET proveedores.tipo_cuenta_id = tipo_cuentas.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            //
            $table->dropForeign(['tipo_cuenta_id']);
            $table->dropColumn('tipo_cuenta_id');
        });
    }
};
