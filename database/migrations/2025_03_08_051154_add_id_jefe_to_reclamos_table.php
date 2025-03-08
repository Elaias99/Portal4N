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
        Schema::table('reclamos', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('id_jefe')->nullable()->after('id_trabajador'); // Agregar id_jefe después de id_trabajador
            $table->foreign('id_jefe')->references('id')->on('jefes')->onDelete('set null'); // Relación con la tabla jefes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            //
            $table->dropForeign(['id_jefe']);
            $table->dropColumn('id_jefe');
        });
    }
};
