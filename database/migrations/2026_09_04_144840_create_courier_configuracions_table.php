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
        Schema::create('courier_configuracions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            $table->foreignId('courier_agente_id')
                ->constrained('courier_agentes');

            /*
             * Cliente y tipo de servicio tal como vienen de Geolice.
             *
             * Se guardan como texto y no como FK: son valores que
             * aparecen y desaparecen segun lo que informe el sistema
             * de origen. De hecho a fin de agosto aparecieron dos
             * servicios nuevos ("Servicio Standar (Cotizacion)" y
             * "Retiro en ruta") que nadie habia configurado.
             */
            $table->string('comerciante', 255);
            $table->string('servicio', 255);

            /*
             * Concatenacion de agente + comerciante + servicio.
             * Es la clave con que la planilla busca la configuracion.
             *
             * La misma combinacion comerciante + servicio puede tener
             * condiciones distintas segun el agente, por eso el agente
             * forma parte de la llave.
             */
            $table->string('llave', 600);

            /*
             * Estado de consideracion para pago. Son TRES valores,
             * no dos: SI (1.853), NO (698) y REVISAR (25).
             *
             * Es independiente de la tabla tarifaria: hay 4 llaves
             * marcadas SI que apuntan a la tabla 0, y 47 marcadas NO
             * que apuntan a una tabla con valor.
             */
            $table->enum('pagar', ['SI', 'NO', 'REVISAR']);

            /*
             * Numero de tabla tarifaria que aplica.
             * Nullable porque puede venir sin definir.
             */
            $table->unsignedTinyInteger('tabla')->nullable();

            $table->timestamps();

            $table->unique(
                ['courier_periodo_id', 'llave'],
                'courier_configuraciones_periodo_llave_unique'
            );

            $table->index(
                ['courier_periodo_id', 'courier_agente_id'],
                'courier_configuraciones_periodo_agente_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_configuracions');
    }
};
