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
        Schema::table('movimientos_compras', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('documento_compra_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_compras', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('documento_compra_id')->nullable(false)->change();
        });
    }
};
