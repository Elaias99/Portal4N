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
        Schema::create('cesiones_factoring', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Datos generales de la cesión
            |--------------------------------------------------------------------------
            | Estos datos se definen una vez y luego pueden reutilizarse
            | al asociar nuevos documentos a la misma cesión.
            |--------------------------------------------------------------------------
            */
            $table->string('cesion', 100)->index();

            $table->foreignId('banco_id')
                ->constrained('bancos')
                ->restrictOnDelete();

            $table->date('fecha_operacion')->index();

            /*
            |--------------------------------------------------------------------------
            | Datos financieros generales de la operación
            |--------------------------------------------------------------------------
            | La comisión pertenece a la operación completa.
            | El monto a recibir se conserva como dato general calculado/registrado.
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('comision_total')->default(0);
            $table->unsignedBigInteger('monto_a_recibir')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Estado general de la cesión
            |--------------------------------------------------------------------------
            | Vigente: aún tiene documentos/operaciones abiertas.
            | Cerrada: todos los documentos asociados quedaron cerrados.
            |--------------------------------------------------------------------------
            */
            $table->string('estado_operacion', 20)
                ->default('Vigente')
                ->index();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices de búsqueda
            |--------------------------------------------------------------------------
            */
            $table->index(['cesion', 'banco_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cesiones_factoring');
    }
};