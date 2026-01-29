<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->string('servicio_manual')
                  ->nullable()
                  ->after('cobranza_compra_id');
        });
    }

    public function down(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->dropColumn('servicio_manual');
        });
    }
};
