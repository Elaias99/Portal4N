<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_honorarios_mensuales_rec', function (Blueprint $table) {
            $table->id();

            // Honorario
            $table->unsignedBigInteger('honorario_mensual_rec_id');

            $table->foreign('honorario_mensual_rec_id', 'mov_honorario_fk')
                ->references('id')
                ->on('honorarios_mensuales_rec')
                ->onDelete('cascade');

            // Usuario
            $table->unsignedBigInteger('usuario_id')->nullable();

            $table->foreign('usuario_id', 'mov_honorario_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Estados
            $table->string('estado_anterior')->nullable();
            $table->string('nuevo_estado')->nullable();

            // Fecha del cambio
            $table->timestamp('fecha_cambio')->nullable();

            // Tipo de movimiento
            $table->string('tipo_movimiento')->nullable();

            // Descripción
            $table->text('descripcion')->nullable();

            // Snapshots
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();

            $table->timestamps();

            $table->index('fecha_cambio');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_honorarios_mensuales_rec');
    }
};
