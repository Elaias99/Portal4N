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
        Schema::table('cargas_transporte', function (Blueprint $table) {
            //
            $table->string('unidad_peso', 20)->nullable()->after('peso_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargas_transporte', function (Blueprint $table) {
            //
            $table->dropColumn('unidad_peso');
        });
    }
};
