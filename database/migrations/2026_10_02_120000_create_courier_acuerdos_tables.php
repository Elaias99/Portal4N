<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Acuerdos: pagos fijos del mes a cada proveedor, con la misma forma
     * que LogisticaCL. Se cargan desde Base_Acuerdos.xlsx (hojas
     * Calendario y Base Acuerdos) y el total de cada fila es
     * costo × (días del calendario − inasistencias + adicionales) × factor.
     */
    public function up(): void
    {
        /* Los días del mes y cuáles son feriado. */
        Schema::create('courier_acuerdo_dias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            $table->date('fecha');
            $table->boolean('es_feriado')->default(false);

            $table->timestamps();

            $table->unique(['courier_periodo_id', 'fecha'], 'courier_acuerdo_dias_periodo_fecha_unique');
        });

        /*
         * Cuántos días se pagan por servicio: un número fijo, o los días
         * de la semana que el servicio trabaja (1 = lunes … 7 = domingo).
         */
        Schema::create('courier_acuerdo_reglas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            /* utf8mb4_bin: el servicio se busca escrito tal cual, como en la plantilla. */
            $table->string('servicio', 160)->collation('utf8mb4_bin');

            /* fijo | dias_semana */
            $table->string('modo', 20);
            $table->json('dias_semana')->nullable();
            $table->unsignedSmallInteger('cantidad_fija')->nullable();

            $table->timestamps();

            $table->unique(['courier_periodo_id', 'servicio'], 'courier_acuerdo_reglas_periodo_servicio_unique');
        });

        /* Una fila de la hoja Base Acuerdos. */
        Schema::create('courier_acuerdos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->cascadeOnDelete();

            $table->unsignedInteger('fila_origen')->nullable();

            /* El proveedor como viene en el archivo, y el del catálogo que calza por RUT. */
            $table->string('proveedor', 255);
            $table->string('rut_proveedor', 25)->nullable();
            $table->foreignId('courier_proveedor_id')
                ->nullable()
                ->constrained('courier_proveedores')
                ->nullOnDelete();

            $table->string('zona', 20)->nullable();
            $table->string('agencia', 100)->nullable();
            $table->string('tipo_servicio', 50)->nullable();
            $table->string('marca', 255)->nullable();
            $table->string('servicio', 160)->collation('utf8mb4_bin');

            $table->unsignedBigInteger('costo');
            $table->unsignedSmallInteger('dias_calendario')->default(0);
            $table->unsignedSmallInteger('inasistencias')->default(0);
            $table->unsignedSmallInteger('adicionales')->default(0);
            $table->unsignedSmallInteger('cantidad')->default(0);
            $table->string('glosa_factor', 255)->nullable();
            $table->unsignedSmallInteger('factor')->default(1);
            $table->unsignedBigInteger('total')->default(0);

            $table->string('razon_social_cliente', 255)->nullable();
            $table->string('comerciante', 255)->nullable();
            $table->string('rut_cliente', 25)->nullable();
            $table->string('nombre_comercial', 255)->nullable();
            $table->string('empresa_mandante', 100)->nullable();

            $table->string('archivo_origen', 255)->nullable();

            $table->timestamps();

            $table->index(['courier_periodo_id', 'courier_proveedor_id'], 'courier_acuerdos_periodo_proveedor_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_acuerdos');
        Schema::dropIfExists('courier_acuerdo_reglas');
        Schema::dropIfExists('courier_acuerdo_dias');
    }
};
