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
            $table->string('archivo')->nullable()->after('fecha_fin');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacacions', function (Blueprint $table) {
            //
            $table->dropColumn('archivo');  // Eliminar el campo si se revierte la migración
        });
    }
};
