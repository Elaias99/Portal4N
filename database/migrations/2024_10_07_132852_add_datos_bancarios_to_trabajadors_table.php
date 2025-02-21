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
            $table->string('banco')->nullable(); // Campo para el banco
            $table->string('numero_cuenta')->nullable(); // Campo para el número de cuenta
            $table->string('tipo_cuenta')->nullable(); // Campo para el tipo de cuenta
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadors', function (Blueprint $table) {
            $table->dropColumn(['banco', 'numero_cuenta', 'tipo_cuenta']);
        });
    }
};
