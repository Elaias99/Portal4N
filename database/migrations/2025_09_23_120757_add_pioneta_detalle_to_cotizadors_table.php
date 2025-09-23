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
        Schema::table('cotizadors', function (Blueprint $table) {
            //
            $table->integer('cantidad_pionetas')->default(0)->after('lleva_pioneta');
            $table->string('jornada_pioneta', 50)->nullable()->after('cantidad_pionetas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizadors', function (Blueprint $table) {
            //
            $table->dropColumn(['cantidad_pionetas', 'jornada_pioneta']);
        });
    }
};
