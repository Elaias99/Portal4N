<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_liquidacion_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('suscripcion_asignacion_id');

            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            $table->string('codigo', 100);

            $table->unsignedBigInteger('costo')->default(0);

            $table->unsignedInteger('q_calendario')->default(0);
            $table->unsignedInteger('q_inasistencia')->default(0);
            $table->unsignedInteger('cantidad')->default(0);

            $table->unsignedBigInteger('total')->default(0);

            $table->timestamps();

            $table->foreign('suscripcion_asignacion_id', 'susc_det_asig_fk')
                ->references('id')
                ->on('suscripcion_asignaciones')
                ->restrictOnDelete();

            $table->index(['anio', 'mes'], 'susc_det_periodo_idx');
            $table->index('codigo', 'susc_det_codigo_idx');
            $table->index('suscripcion_asignacion_id', 'susc_det_asig_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_liquidacion_detalles');
    }
};