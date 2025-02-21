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
            $table->foreignId('id_jefe')->nullable()->constrained('jefes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadors', function (Blueprint $table) {
            //
            $table->dropForeign(['id_jefe']);
            $table->dropColumn('id_jefe');
        });
    }
};
