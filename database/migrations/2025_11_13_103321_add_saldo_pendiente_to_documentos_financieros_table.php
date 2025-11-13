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
        Schema::table('documentos_financieros', function (Blueprint $table) {
            //
            $table->bigInteger('saldo_pendiente')
                  ->nullable()
                  ->after('monto_total'); // ajusta ubicación si lo deseas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_financieros', function (Blueprint $table) {
            //
            $table->dropColumn('saldo_pendiente');
        });
    }
};
