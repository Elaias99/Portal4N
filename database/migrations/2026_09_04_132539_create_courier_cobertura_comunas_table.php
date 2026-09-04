<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_cobertura_comunas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            $table->foreignId('courier_agente_id')
                ->constrained('courier_agentes');

            /*
             * Comuna de destino tal como llega desde Geolice.
             *
             * Se guarda el texto crudo a propósito: de las 585
             * localidades de la planilla, 179 no existen como comuna
             * real. Hay errores de tipeo (ANTOFAAGSTA), texto corrupto
             * por encoding, lugares que no son comunas (CD QUILICURA)
             * y desagregaciones operativas (BUIN 1, CONCEPCION II).
             */
            $table->string('localidad', 255);

            /*
             * lower(localidad). Es la clave de búsqueda y replica el
             * VLOOKUP de Excel: insensible a mayúsculas pero SENSIBLE
             * a tildes y espacios.
             *
             * No normalizar más que esto. Quitar tildes colapsaría 69
             * localidades distintas (Maipú con MAIPU) y cambiaría a
             * qué agente se asigna cada envío.
             */
            $table->string('localidad_clave', 255);

            /*
             * Zona operativa: "Regiones" o "RM".
             *
             * Vive acá y no en el agente porque "Envio externo" opera
             * en ambas zonas según la comuna.
             */
            $table->string('zona', 50)->nullable();

            /*
             * Columnas PAGAR RETORNO y VALOR/RETORNO de la hoja
             * Operador. Solo las usa el flujo de retornos, donde el
             * monto es fijo por comuna y no sale de la matriz de pesos.
             */
            $table->boolean('pagar_retorno')->default(false);
            $table->integer('valor_retorno')->nullable();

            $table->timestamps();

            /*
             * Una localidad no puede estar dos veces en el mismo
             * período. Verificado: las 585 son únicas bajo esta clave.
             */
            $table->unique(
                ['courier_periodo_id', 'localidad_clave'],
                'courier_cobertura_periodo_localidad_unique'
            );

            $table->index(
                ['courier_periodo_id', 'courier_agente_id'],
                'courier_cobertura_periodo_agente_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_cobertura_comunas');
    }
};