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
        Schema::table('cruces', function (Blueprint $table) {
            //
            // Agregamos el campo proveedor_id (permitiendo null inicialmente)
            $table->unsignedBigInteger('proveedor_id')->nullable()->after('documento_financiero_id');

            // Creamos la relación con la tabla proveedores
            $table->foreign('proveedor_id')
                ->references('id')
                ->on('proveedores')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cruces', function (Blueprint $table) {
            //
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });
    }
};
