<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('honorario_pagos_programados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('honorario_mensual_rec_id')
                ->constrained('honorarios_mensuales_rec')
                ->cascadeOnDelete();

            $table->date('fecha_programada');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->unique('honorario_mensual_rec_id');
            $table->index('fecha_programada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honorario_pagos_programados');
    }
};