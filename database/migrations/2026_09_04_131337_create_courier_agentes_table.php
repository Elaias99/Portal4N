<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_agentes', function (Blueprint $table) {
            $table->id();

            /*
             * Nombre del agente tal como aparece en la columna
             * ComunaMatriz de la hoja Operador.
             *
             * Ejemplos:
             * "4N RM"
             * "Mercasur"
             * "Daphne Katalina Meza Ramos (San Fernando)"
             *
             * IMPORTANTE: este valor participa literalmente en la
             * construcción de la Llave (agente + comerciante +
             * servicio). Cambiarlo altera el cruce con la
             * configuración de pago.
             */
            $table->string('nombre', 255);

            /*
             * Permite retirar un agente de circulación sin borrarlo
             * ni perder los períodos históricos donde sí operó.
             *
             * Es un caso real: al comparar la planilla con la tabla
             * `operadores` aparecieron agentes que dejaron de operar
             * (Veronica Moya en Balmaceda, reemplazada por Tamara
             * Maldonado) y otros que se renombraron.
             */
            $table->boolean('activo')->default(true);

            $table->timestamps();

            /*
             * El nombre es la clave de negocio: no puede repetirse.
             */
            $table->unique(
                'nombre',
                'courier_agentes_nombre_unique'
            );

            /*
             * Consulta habitual del listado de agentes vigentes.
             */
            $table->index(
                'activo',
                'courier_agentes_activo_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_agentes');
    }
};