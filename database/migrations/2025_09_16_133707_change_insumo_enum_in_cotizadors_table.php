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
        // Primero actualizamos los valores existentes (con → proveedor, sin → cliente)
        DB::table('cotizadors')
            ->where('insumo', 'con')
            ->update(['insumo' => 'proveedor']);

        DB::table('cotizadors')
            ->where('insumo', 'sin')
            ->update(['insumo' => 'cliente']);

        // Ahora modificamos la definición del campo enum
        Schema::table('cotizadors', function (Blueprint $table) {
            $table->enum('insumo', ['proveedor', 'cliente'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertimos si hacemos rollback
        DB::table('cotizadors')
            ->where('insumo', 'proveedor')
            ->update(['insumo' => 'con']);

        DB::table('cotizadors')
            ->where('insumo', 'cliente')
            ->update(['insumo' => 'sin']);

        Schema::table('cotizadors', function (Blueprint $table) {
            $table->enum('insumo', ['con', 'sin'])->nullable()->change();
        });
    }
};
