<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->unsignedBigInteger('centro_costo_id')->nullable()->after('proveedor_id');
            $table->foreign('centro_costo_id')->references('id')->on('centros_costos')->onDelete('set null');
            $table->dropColumn('centro_costo'); // Eliminamos la columna anterior
        });
    }

    public function down()
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->string('centro_costo')->nullable();
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn('centro_costo_id');
        });
    }
};

