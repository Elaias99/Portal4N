<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Insertar los bancos existentes
        $bancos = [
            'NOREGISTRO', 'BANCO ESTADO', 'BANCO CHILE', 'BANCO FALABELLA',
            'BANCO SANTANDER', 'BANCO BCI', 'BANCO BICE', 'BANCO CONSORCIO',
            'BANCO SCOTIABANK', 'BANCO SECURITY', 'BANCO CORPBANCA', 
            'BANCO RIPLEY', 'BANCO ITAU', 'BANCO PARIS', 'BANCO DEL DESARROLLO',
            'BANCO COPEUCH', 'BANCO BBVA', 'WEBPAY PAGO ONLINE', 
            'MERCADO PAGO', 'TENPO'
        ];

        foreach ($bancos as $banco) {
            DB::table('bancos')->insert(['nombre' => $banco]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
