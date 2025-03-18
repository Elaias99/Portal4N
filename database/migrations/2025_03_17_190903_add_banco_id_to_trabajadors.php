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
            
            $table->unsignedBigInteger('banco_id')->nullable()->after('sistema_trabajo_id');

            $table->foreign('banco_id')->references('id')->on('bancos')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            $table->dropForeign(['banco_id']);
            $table->dropColumn('banco_id');
        });
    }
};
