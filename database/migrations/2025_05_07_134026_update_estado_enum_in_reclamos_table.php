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
            $table->enum('estado', ['pendiente', 'en_revision', 'resuelto', 'rechazado', 'cerrado'])->default('pendiente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            //
            $table->enum('estado', ['pendiente', 'en_revision', 'resuelto', 'rechazado'])->default('pendiente')->change();
        });
    }
};
