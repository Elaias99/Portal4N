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
        Schema::create('comunas_operadores', function (Blueprint $table) {
            $table->id();

            $table->string('nombre_comuna')->unique();
            $table->foreignId('matriz_id')
                ->constrained('comunas_matriz')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comunas_operadores');
    }
};
