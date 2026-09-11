<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_proveedores', function (Blueprint $table) {
            $table->id();

            /*
             * Operador + Usuario tal como quedan en BaseCL (columnas M y N).
             *
             * Operador es texto y no FK a courier_agentes porque la hoja
             * tiene 52 operadores y sólo 33 son agentes de Geolice; el
             * resto son proveedores de los pagos manuales (Ruta CV,
             * Servicios, Visitas…).
             */
            $table->string('operador', 100);
            $table->string('usuario', 100);

            /*
             * operador . usuario, sin separador. Es la llave con que
             * BaseCL busca a quién se le paga. Única: la hoja tiene 2
             * repetidas y el BUSCARV toma la primera; el importador
             * hará lo mismo y las reportará.
             */
            $table->string('llave', 200)->unique();

            $table->string('transportista', 100)->nullable();
            $table->string('razon_social', 150);
            $table->string('rut', 20)->nullable();
            $table->string('empresa', 50)->nullable();

            /*
             * Texto libre de Operaciones. Valores actuales: Factura (222),
             * Sin Documento (26), Boleta de Honorarios (24),
             * Factura Exenta (6), Boleta de Honorarios Tercero (1).
             *
             * El IVA se aplica sólo cuando es exactamente "Factura".
             */
            $table->string('tipo_documento', 50);

            $table->string('titular_banco', 150)->nullable();
            $table->string('rut_titular_banco', 20)->nullable();
            $table->string('banco', 100)->nullable();
            $table->string('tipo_cuenta', 50)->nullable();
            $table->string('nro_cuenta', 50)->nullable();

            /*
             * Puente hacia el maestro de proveedores de Portal4N, que ya
             * tiene los datos bancarios y lo usa Suscripciones. Se llena
             * después, cuando se confirmen las equivalencias.
             */
            $table->foreignId('cobranza_compra_id')
                ->nullable()
                ->constrained('cobranza_compras')
                ->nullOnDelete();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('operador', 'courier_proveedores_operador_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_proveedores');
    }
};