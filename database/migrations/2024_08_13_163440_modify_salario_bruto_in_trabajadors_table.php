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
            $table->decimal('salario_bruto', 12, 2)->change();  // Cambia el tipo de dato a decimal(12, 2)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            $table->decimal('salario_bruto', 8, 2)->change();  // Revertir al tipo de dato original si es necesario
        });
    }
};
