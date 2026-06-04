<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_asignaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('suscripcion_proveedor_id')
                ->constrained('suscripcion_proveedores')
                ->restrictOnDelete();

            $table->foreignId('suscripcion_transportista_id')
                ->constrained('suscripcion_transportistas')
                ->restrictOnDelete();

            $table->string('punto_1')->nullable();
            $table->string('origen_gasto')->nullable();
            $table->string('punto_2')->nullable();

            $table->string('codigo', 100);

            $table->string('servicio')->nullable();

            $table->unsignedBigInteger('costo')->default(0);

            $table->timestamps();

            $table->index('codigo', 'susc_asig_codigo_idx');
            $table->index('servicio', 'susc_asig_servicio_idx');

            $table->index(
                ['suscripcion_proveedor_id', 'suscripcion_transportista_id'],
                'susc_asig_prov_trans_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_asignaciones');
    }
};