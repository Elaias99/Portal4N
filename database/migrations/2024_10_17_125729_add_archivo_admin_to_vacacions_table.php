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
        Schema::table('vacacions', function (Blueprint $table) {
            //
            $table->string('archivo_admin')->nullable();  // Campo para el archivo PDF que adjunta el administrador
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacacions', function (Blueprint $table) {
            //
            $table->dropColumn('archivo_admin');
        });
    }
};
