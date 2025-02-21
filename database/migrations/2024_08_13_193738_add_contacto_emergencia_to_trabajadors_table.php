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
            $table->string('nombre_emergencia')->nullable();
            $table->string('contacto_emergencia')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            $table->dropColumn('nombre_emergencia');
            $table->dropColumn('contacto_emergencia');
        });
    }
};
