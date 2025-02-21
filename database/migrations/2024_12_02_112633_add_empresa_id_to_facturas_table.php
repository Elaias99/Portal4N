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
        Schema::table('facturas', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')->nullable(); // Nueva columna
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade'); // Clave foránea
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            //
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
