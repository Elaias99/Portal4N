<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factories', function (Blueprint $table) {
            $table->id();

            // Documento CxC afectado
            $table->foreignId('documento_financiero_id')
                ->unique()
                ->constrained('documentos_financieros')
                ->cascadeOnDelete();

            // Banco / entidad Factory
            $table->foreignId('banco_id')
                ->constrained('bancos')
                ->restrictOnDelete();

            // RUT ingresado para el factory
            $table->string('rut_factory', 20);

            // Fecha automática del registro
            $table->date('fecha_factory');

            // Snapshot del saldo que se cerró por Factory
            $table->unsignedBigInteger('monto')->default(0);

            // Usuario que registró el estado
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('rut_factory');
            $table->index('fecha_factory');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factories');
    }
};