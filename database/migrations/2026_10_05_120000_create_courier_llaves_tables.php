<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * La llave de pago de Operaciones: RUT del proveedor + RUT del cliente
     * + código del servicio → se paga o no, y con qué tabla. Reemplaza a
     * courier_configuracions (agente + comerciante + servicio escritos).
     *
     * Las cinco tablas se cargan juntas desde Llaves_Courier.xlsx con
     * courier:importar-llaves; cada carga reemplaza todo lo anterior.
     * Las claves (_clave) van en binario, como el resto de Courier.
     */
    public function up(): void
    {
        /* Agente → RUT del proveedor que entrega ahí. */
        Schema::create('courier_llave_agentes', function (Blueprint $table) {
            $table->id();
            $table->string('agente', 255);
            $table->string('agente_clave', 255)->collation('utf8mb4_bin')->unique();
            $table->string('rut_proveedor', 25);
            $table->string('razon_social', 255)->nullable();
            $table->timestamps();
        });

        /* Comerciante como lo escribe Geolice → RUT del cliente. */
        Schema::create('courier_llave_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('comerciante', 255);
            $table->string('comerciante_clave', 255)->collation('utf8mb4_bin')->unique();
            $table->string('rut_cliente', 25);
            $table->string('razon_social', 255)->nullable();
            $table->timestamps();
        });

        /* Servicio como lo escribe Geolice → código del servicio. */
        Schema::create('courier_llave_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('servicio', 255);
            $table->string('servicio_clave', 255)->collation('utf8mb4_bin')->unique();
            $table->unsignedInteger('codigo');
            $table->timestamps();
        });

        /*
         * La llave. El agente sólo desempata: cuando un proveedor tiene
         * llaves distintas según el agente, manda la del agente del bulto.
         */
        Schema::create('courier_llaves', function (Blueprint $table) {
            $table->id();
            $table->string('rut_proveedor', 25);
            $table->string('agente', 255)->nullable();
            $table->string('agente_clave', 255)->collation('utf8mb4_bin')->nullable();
            $table->string('rut_cliente', 25);
            $table->string('comerciante', 255)->nullable();
            $table->unsignedInteger('codigo_servicio');
            $table->string('servicio', 255)->nullable();
            $table->enum('pagar', ['SI', 'NO', 'REVISAR']);
            $table->unsignedTinyInteger('tabla')->nullable();
            $table->timestamps();

            $table->index(['rut_proveedor', 'rut_cliente', 'codigo_servicio'], 'courier_llaves_rut_rut_servicio_index');
        });

        /*
         * Personal de 4N: lo que entrega un repartidor que en realidad
         * trabaja para otro proveedor se le paga a ese proveedor.
         * Nuevo RUT "N/A" = se queda en 4N.
         */
        Schema::create('courier_llave_repartidores', function (Blueprint $table) {
            $table->id();
            $table->string('rut_proveedor', 25);
            $table->string('agente', 255);
            $table->string('repartidor', 255);
            $table->string('clave', 600)->collation('utf8mb4_bin')->unique();
            $table->string('nuevo_rut_proveedor', 25);
            $table->timestamps();
        });

        /* El RUT al que se le asignó el bulto, aunque no esté en courier_proveedores. */
        Schema::table('courier_bultos', function (Blueprint $table) {
            $table->string('rut_proveedor', 25)->nullable()->after('courier_proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::table('courier_bultos', function (Blueprint $table) {
            $table->dropColumn('rut_proveedor');
        });

        Schema::dropIfExists('courier_llave_repartidores');
        Schema::dropIfExists('courier_llaves');
        Schema::dropIfExists('courier_llave_servicios');
        Schema::dropIfExists('courier_llave_clientes');
        Schema::dropIfExists('courier_llave_agentes');
    }
};
