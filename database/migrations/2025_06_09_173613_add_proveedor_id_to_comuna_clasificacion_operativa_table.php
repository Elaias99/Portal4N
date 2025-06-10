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
        Schema::table('comuna_clasificacion_operativa', function (Blueprint $table) {
            //
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comuna_clasificacion_operativa', function (Blueprint $table) {
            //
        });
    }
};
