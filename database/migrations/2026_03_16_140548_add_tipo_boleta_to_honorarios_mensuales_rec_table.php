<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->string('tipo_boleta', 50)
                ->default('Boleta Honorario')
                ->after('servicio_manual');
        });

        DB::table('honorarios_mensuales_rec')
            ->whereNull('tipo_boleta')
            ->update([
                'tipo_boleta' => 'Boleta Honorario'
            ]);
    }

    public function down(): void
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {
            $table->dropColumn('tipo_boleta');
        });
    }
};