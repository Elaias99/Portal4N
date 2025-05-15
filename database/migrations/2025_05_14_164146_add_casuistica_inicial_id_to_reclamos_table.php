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
            $table->unsignedBigInteger('casuistica_inicial_id')->nullable()->after('descripcion');
            $table->foreign('casuistica_inicial_id')->references('id')->on('casuisticas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            //
            $table->dropForeign(['casuistica_inicial_id']);
            $table->dropColumn('casuistica_inicial_id');
        });
    }
};
