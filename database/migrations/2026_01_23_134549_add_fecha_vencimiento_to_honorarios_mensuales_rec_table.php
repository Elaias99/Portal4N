<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {

            $table->date('fecha_vencimiento')
                  ->nullable()
                  ->after('fecha_emision');

        });
    }

    public function down()
    {
        Schema::table('honorarios_mensuales_rec', function (Blueprint $table) {

            $table->dropColumn('fecha_vencimiento');

        });
    }
};
