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
        Schema::table('empresas', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('empresas', 'rut')) {
                $table->string('rut', 20)->nullable()->after('logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            //
            if (Schema::hasColumn('empresas', 'rut')) {
                $table->dropColumn('rut');
            }
        });
    }
};
