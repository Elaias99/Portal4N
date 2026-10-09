<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Registro del envío real de cada pre-factura.
         *
         * Una pre-factura es:
         * proveedor efectivo + año + mes + grupo de pre-factura.
         *
         * Permite que un envío cortado se retome enviando sólo
         * las pre-facturas que todavía no salieron.
         */
        Schema::create('suscripcion_prefactura_envios', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            /*
             * Proveedor efectivo que recibe la pre-factura.
             */
            $table->unsignedBigInteger('suscripcion_proveedor_id');

            /*
             * Clave normalizada del grupo (GENERAL si no tiene).
             */
            $table->string('grupo_prefactura', 100);

            /*
             * enviando | enviado | omitido | fallido
             */
            $table->string('estado', 20);

            $table->string('correo')->nullable();
            $table->string('archivo')->nullable();
            $table->string('oc', 30)->nullable();

            /*
             * Motivo de omisión o error del último intento.
             */
            $table->text('mensaje')->nullable();

            $table->timestamp('ultimo_intento_at')->nullable();
            $table->timestamp('enviado_at')->nullable();

            $table->timestamps();

            $table->foreign(
                'suscripcion_proveedor_id',
                'sus_pref_env_prov_fk'
            )
                ->references('id')
                ->on('suscripcion_proveedores')
                ->restrictOnDelete();

            $table->unique(
                [
                    'anio',
                    'mes',
                    'suscripcion_proveedor_id',
                    'grupo_prefactura',
                ],
                'sus_pref_env_prefactura_uq'
            );

            $table->index(
                [
                    'anio',
                    'mes',
                    'estado',
                ],
                'sus_pref_env_periodo_estado_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'suscripcion_prefactura_envios'
        );
    }
};
