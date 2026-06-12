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
        Schema::create('suscripcion_comisiones_mensuales', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('suscripcion_asignacion_id');

            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            $table->string('codigo')->nullable();
            $table->integer('costo')->default(0);
            $table->integer('cantidad')->default(1);
            $table->integer('total')->default(0);

            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->foreign('suscripcion_asignacion_id', 'sus_com_mens_asig_fk')
                ->references('id')
                ->on('suscripcion_asignaciones')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(
                ['suscripcion_asignacion_id', 'anio', 'mes'],
                'sus_com_mens_asig_periodo_uk'
            );

            $table->index(['anio', 'mes'], 'sus_com_mens_periodo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripcion_comisiones_mensuales');
    }
};