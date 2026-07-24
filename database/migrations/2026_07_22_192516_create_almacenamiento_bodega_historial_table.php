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
        Schema::create('almacenamiento_bodega_historial', function (Blueprint $table) {
            $table->id();

            /*
            * Conservamos el ID original del producto.
            * Intencionalmente no es una clave foránea,
            * porque el producto podrá ser eliminado.
            */
            $table->unsignedBigInteger('almacenamiento_bodega_id')
                    ->index();

            /*
            * Copia del nombre para saber qué producto
            * fue creado o eliminado, aunque ya no exista.
            */
            $table->string('nombre_producto');

            /*
            * Valores iniciales:
            * CREADO o ELIMINADO.
            */
            $table->string('accion', 20);



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almacenamiento_bodega_historial');
    }
};
