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
            $table->string('giro')->after('nombre');
            $table->string('direccion')->after('giro');
            $table->string('cta_corriente')->after('direccion');
            $table->string('mail_formalizado')->after('cta_corriente');

            $table->unsignedBigInteger('banco_id')->nullable()->after('mail_formalizado');
            $table->unsignedBigInteger('comuna_id')->nullable()->after('banco_id');

            $table->foreign('banco_id')->references('id')->on('bancos');
            $table->foreign('comuna_id')->references('id')->on('comunas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign(['banco_id']);
            $table->dropForeign(['comuna_id']);

            $table->dropColumn([
                'giro',
                'direccion',
                'cta_corriente',
                'mail_formalizado',
                'banco_id',
                'comuna_id',
            ]);
        });
    }
};
