<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_conceptos_pago_variable', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 80)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();

            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);

            $table->timestamps();

            $table->index(['activo', 'orden'], 'scp_variable_activo_orden_idx');
        });

        Schema::table('suscripcion_ajustes_mensuales', function (Blueprint $table) {
            $table
                ->foreignId('concepto_pago_variable_id')
                ->nullable()
                ->after('tipo_ajuste')
                ->constrained('suscripcion_conceptos_pago_variable')
                ->nullOnDelete();

            $table
                ->string('concepto_pago_variable_manual', 150)
                ->nullable()
                ->after('concepto_pago_variable_id');

            $table
                ->string('concepto_pago_variable_snapshot', 150)
                ->nullable()
                ->after('concepto_pago_variable_manual');

            $table->index(
                ['concepto_pago_variable_id'],
                'sam_concepto_pago_variable_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('suscripcion_ajustes_mensuales', function (Blueprint $table) {
            $table->dropForeign(['concepto_pago_variable_id']);

            $table->dropIndex('sam_concepto_pago_variable_idx');

            $table->dropColumn([
                'concepto_pago_variable_id',
                'concepto_pago_variable_manual',
                'concepto_pago_variable_snapshot',
            ]);
        });

        Schema::dropIfExists('suscripcion_conceptos_pago_variable');
    }
};