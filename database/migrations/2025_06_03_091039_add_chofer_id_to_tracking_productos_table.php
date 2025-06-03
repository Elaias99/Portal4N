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
        Schema::table('tracking_productos', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('chofer_id')->nullable()->after('area_id');
            $table->foreign('chofer_id')->references('id')->on('trabajadors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_productos', function (Blueprint $table) {
            //
            $table->dropForeign(['chofer_id']);
            $table->dropColumn('chofer_id');
        });
    }
};
