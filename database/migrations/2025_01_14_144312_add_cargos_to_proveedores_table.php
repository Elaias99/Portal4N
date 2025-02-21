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
        Schema::table('proveedores', function (Blueprint $table) {
            //
            $table->string('cargo_contacto1')->nullable(); // Cargo para el primer contacto
            $table->string('cargo_contacto2')->nullable(); // Cargo para el segundo contacto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            //
            $table->dropColumn(['cargo_contacto1', 'cargo_contacto2']);
        });
    }
};
