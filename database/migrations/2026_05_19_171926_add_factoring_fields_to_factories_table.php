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
        Schema::table('factories', function (Blueprint $table) {
            $table->string('cesion')->nullable()->after('rut_factory');

            $table->unsignedBigInteger('saldo_liquido')
                ->nullable()
                ->after('monto');

            $table->unsignedBigInteger('diferencia')
                ->nullable()
                ->after('saldo_liquido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropColumn([
                'cesion',
                'saldo_liquido',
                'diferencia',
            ]);
        });
    }
};