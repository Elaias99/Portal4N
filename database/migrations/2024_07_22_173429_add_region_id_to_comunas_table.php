<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comunas', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->after('Nombre');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('comunas', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};

