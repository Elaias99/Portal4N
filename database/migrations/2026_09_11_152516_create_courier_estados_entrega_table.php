<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_estados_entrega', function (Blueprint $table) {
            $table->id();

            /*
             * Estado de entrega tal como lo informa Geolice.
             * Hoy son 7: Anulado, En reparto, En tránsito, Entregado,
             * Fallido, Pendiente, Retirado.
             */
            $table->string('estado', 100)->unique();

            /*
             * PAGAR: el bulto sigue en el flujo.
             * DESCONTAR: se excluye antes de calcular.
             *
             * Ojo: "Fallido" está en PAGAR. Es decisión de Operaciones,
             * no un error de carga.
             */
            $table->enum('considerar', ['PAGAR', 'DESCONTAR']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_estados_entrega');
    }
};