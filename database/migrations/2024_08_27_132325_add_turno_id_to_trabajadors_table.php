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
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            $table->foreignId('turno_id')->nullable()->constrained()->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            // Eliminar la clave foránea
            $table->dropForeign(['turno_id']);
            // Eliminar la columna 'turno_id'
            $table->dropColumn('turno_id');
        });
    }
};
