<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobranza_compras', function (Blueprint $table) {
            $table->string('correo_suscripciones')
                ->nullable()
                ->after('responsable');
        });
    }

    public function down(): void
    {
        Schema::table('cobranza_compras', function (Blueprint $table) {
            $table->dropColumn('correo_suscripciones');
        });
    }
};
