<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_bultos', function (Blueprint $table) {
            $table->id();

            /*
             * Período de pago en que el bulto entró al sistema.
             *
             * Las descargas de Geolice se traslapan a propósito para
             * alcanzar los bultos entregados tarde; si un bulto ya
             * existe de un período anterior, el importador no lo pisa
             * y lo reporta. Eso reemplaza la columna PagadoMesAnterior
             * de la planilla.
             */
            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            /*
             * Nombre del archivo desde donde se cargó la fila
             * (export-6399-packages.xlsx). Para rastrear el origen.
             */
            $table->string('archivo_origen', 100)->nullable();

            /* ------------------------------------------------------
             * A. Columnas tal como vienen de Geolice (las 31).
             * Solo se limpia el formato: texto → número/fecha.
             * ------------------------------------------------------ */

            /*
             * "Seguimiento paquete": código + sufijo (4N202608148928-529).
             * Es la identidad del bulto en toda la planilla.
             */
            $table->string('seguimiento', 30);

            /*
             * "Código de seguimiento": el mismo código sin sufijo.
             * Es lo que trae el CSV de pesajes de bodega.
             */
            $table->string('codigo', 20);

            /*
             * "Peso": llega como texto ("0.31 kg") o vacío.
             * Es el peso declarado por el comerciante, no el de bodega.
             */
            $table->decimal('peso_declarado', 8, 2)->nullable();
            $table->decimal('largo', 8, 2)->nullable();
            $table->decimal('ancho', 8, 2)->nullable();
            $table->decimal('alto', 8, 2)->nullable();

            $table->string('codigo_externo', 100)->nullable();

            /*
             * "Centro de costo" de Geolice es un código del cliente.
             * NO es el "centro de costo" del jefe de Operaciones
             * (la tabla tarifaria); ese va en la columna `tabla`.
             */
            $table->string('centro_costo', 50)->nullable();
            $table->string('orden_compra', 50)->nullable();
            $table->string('guia_despacho', 50)->nullable();

            /*
             * Texto tal cual; se cruza con courier_estados_entrega.estado.
             * No es FK porque Geolice puede informar estados nuevos y el
             * importador debe cargarlos igual y avisarlos.
             */
            $table->string('estado_entrega', 100);
            $table->unsignedTinyInteger('intentos_entrega')->default(0);

            /*
             * Comerciante y servicio tal cual llegan, sin trim: junto al
             * agente forman la llave de courier_configuracions.
             */
            $table->string('comerciante', 255);
            $table->string('servicio', 255);
            $table->string('campana', 255)->nullable();

            $table->string('destinatario_nombre', 255)->nullable();
            $table->string('destinatario_empresa', 255)->nullable();
            $table->string('direccion', 255)->nullable();

            /*
             * Texto tal cual; se cruza con courier_cobertura_comunas
             * usando la misma clave (minúsculas, con tildes).
             */
            $table->string('comuna_destino', 255)->nullable();

            /*
             * Los teléfonos llegan con un apóstrofe adelante ('+569…);
             * el importador lo quita.
             */
            $table->string('destinatario_telefono', 50)->nullable();
            $table->string('destinatario_email', 255)->nullable();

            /*
             * "Valor": llega como "$6,133.74". Es el valor del envío
             * declarado en Geolice; no tiene relación con el pago.
             */
            $table->decimal('valor_envio', 12, 2)->nullable();

            /*
             * Fechas: llegan como texto "14/08/2026 21:11" y "17/08/2026".
             * Pueden venir vacías.
             */
            $table->dateTime('fecha_recepcion')->nullable();
            $table->date('entrega_estimada')->nullable();
            $table->dateTime('fecha_entrega')->nullable();

            /* "Retiro en comerciante": Sí / No. */
            $table->boolean('retiro_en_comerciante')->default(false);

            $table->string('bodega_retiro', 100)->nullable();
            $table->string('ruta_entrega', 50)->nullable();
            $table->string('repartidor_nombre', 100)->nullable();
            $table->string('repartidor_telefono', 50)->nullable();
            $table->string('usuario_entrega', 100)->nullable();

            /* ------------------------------------------------------
             * B. Columnas que llena el sistema al calcular.
             * Vacías al importar. Son las columnas que la planilla
             * va agregando en BaseGeolize-trabajada.
             * ------------------------------------------------------ */

            /* Hoja Operador: comuna → agente y zona. */
            $table->foreignId('courier_agente_id')
                ->nullable()
                ->constrained('courier_agentes')
                ->nullOnDelete();
            $table->string('zona', 50)->nullable();

            /*
             * Hoja PagosCentroCostos: agente + comerciante + servicio.
             * Se guarda la configuración usada y, además, copia de
             * pagar y tabla: si el catálogo cambia después, el bulto
             * conserva lo que se le aplicó.
             */
            $table->foreignId('courier_configuracion_id')
                ->nullable()
                ->constrained('courier_configuracions')
                ->nullOnDelete();
            $table->enum('considerar_pago', ['SI', 'NO', 'REVISAR'])->nullable();
            $table->unsignedTinyInteger('tabla')->nullable();

            /*
             * Kilos. peso_bodega viene del CSV de pesajes (PesoReal);
             * peso_pago es el kilo que finalmente se usó y origen_peso
             * dice de dónde salió: bodega, declarado (PesoTransformado)
             * o x (sin peso por ninguna fuente → 1 kg).
             */
            $table->unsignedSmallInteger('peso_bodega')->nullable();
            $table->unsignedSmallInteger('peso_pago')->nullable();
            $table->enum('origen_peso', ['bodega', 'declarado', 'x'])->nullable();

            /* Hoja Pesos: valor a pagar según tabla y kilos (CLP). */
            $table->unsignedInteger('valor')->nullable();

            /*
             * Resultado final (hojas Estados, especiales, Retornos, Blue,
             * ConsiderarPago, PagadoMesAnterior). motivo guarda por qué
             * se descontó, para el resumen de detección.
             */
            $table->enum('estado_pago', ['PAGAR', 'DESCONTAR'])->nullable();
            $table->string('motivo', 100)->nullable();

            $table->timestamp('calculado_at')->nullable();

            $table->timestamps();

            /*
             * Un bulto existe una sola vez en el sistema, aunque
             * aparezca en varias descargas.
             */
            $table->unique('seguimiento', 'courier_bultos_seguimiento_unique');

            /* Cruce con el CSV de pesajes (código sin sufijo). */
            $table->index('codigo', 'courier_bultos_codigo_index');

            $table->index(
                ['courier_periodo_id', 'courier_agente_id'],
                'courier_bultos_periodo_agente_index'
            );

            $table->index(
                ['courier_periodo_id', 'estado_pago'],
                'courier_bultos_periodo_estado_pago_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_bultos');
    }
};
