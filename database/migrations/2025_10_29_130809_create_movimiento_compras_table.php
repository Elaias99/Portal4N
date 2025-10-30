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
        Schema::create('movimientos_compras', function (Blueprint $table) {
            $table->id();


            $table->foreignId('documento_compra_id')->constrained('documentos_compras')->cascadeOnDelete();

            $table->foreignId('usuario_id')->constrained('users');
            $table->string('estado_anterior')->nullable();
            $table->string('nuevo_estado')->nullable();
            $table->timestamp('fecha_cambio')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_compras');
    }
};
