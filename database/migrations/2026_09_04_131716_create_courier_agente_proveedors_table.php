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
        Schema::create('courier_agente_proveedors', function (Blueprint $table) {
            $table->id();

             $table->foreignId('courier_agente_id')
                ->constrained('courier_agentes')
                ->cascadeOnDelete();

            /*
             * Titular real tal como aparece en la columna
             * NombreProveedor de la hoja Operador.
             *
             * En 29 de 33 agentes coincide con el nombre del agente.
             * En 4 no, y dos de ellos tienen más de un titular:
             *   "Paula Valencia (Viña del Mar)"
             *   "Jose Caneo (La Ligua)"
             *
             * Se guarda como texto. El vínculo con la entidad que
             * recibe el pago queda para una versión posterior, cuando
             * Operaciones confirme las equivalencias.
             */
            $table->string('nombre_proveedor', 255);

            /*
             * Titular por defecto cuando el agente tiene más de uno.
             */
            $table->boolean('principal')->default(false);

            $table->timestamps();

            $table->unique(
                ['courier_agente_id', 'nombre_proveedor'],
                'courier_agente_proveedor_unique'
            );


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_agente_proveedors');
    }
};
