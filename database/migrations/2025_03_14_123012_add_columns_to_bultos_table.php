<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('bultos', function (Blueprint $table) {
            $table->string('id_envio', 50)->nullable();
            $table->string('atencion', 100)->nullable();
            $table->string('numero_destino', 20)->nullable();
            $table->string('depto_destino', 50)->nullable();
            $table->string('razon_social', 255)->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('nombre_campana', 255)->nullable();
            $table->text('descripcion_bulto')->nullable();
            $table->text('observacion')->nullable();
            $table->string('referencia', 255)->nullable();
            $table->decimal('peso', 10, 2)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('mail', 255)->nullable();
            $table->string('unidad', 50)->nullable();
        });
    }

    public function down()
    {
        Schema::table('bultos', function (Blueprint $table) {
            $table->dropColumn([
                'id_envio', 'atencion', 'numero_destino', 'depto_destino', 'razon_social',
                'fecha_entrega', 'ubicacion', 'region', 'nombre_campana', 'descripcion_bulto',
                'observacion', 'referencia', 'peso', 'telefono', 'mail', 'unidad'
            ]);
        });
    }
};
