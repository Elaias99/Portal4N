<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * El cierre del mes, como en LogisticaCL: lo que se paga queda
     * guardado en courier_pagos_cerrados con su OC e impuesto, y esa
     * tabla no se puede modificar ni borrar (lo impiden los triggers).
     * Un bulto que está ahí no se vuelve a pagar en otro período.
     */
    public function up(): void
    {
        Schema::create('courier_cierres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->unique()
                ->constrained('courier_periodos')
                ->restrictOnDelete();

            $table->unsignedInteger('registros');
            $table->unsignedInteger('ordenes_compra');
            $table->unsignedBigInteger('neto');
            $table->unsignedBigInteger('iva');
            $table->unsignedBigInteger('retencion');
            $table->unsignedBigInteger('total');
            $table->foreignId('user_id')->nullable();
            $table->timestamp('closed_at');
        });

        Schema::create('courier_pagos_cerrados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_periodo_id')
                ->constrained('courier_periodos')
                ->restrictOnDelete();

            /* bulto | acuerdo | proceso, y el id de la fila de origen. */
            $table->string('origen', 10);
            $table->unsignedBigInteger('origen_id');

            /* Sólo los bultos: es lo que impide pagar dos veces el mismo. */
            $table->string('seguimiento', 30)->nullable()->unique();

            $table->string('tipo_pago', 20);
            $table->string('zona', 20);
            $table->unsignedBigInteger('courier_proveedor_id');
            $table->string('rut_proveedor', 25);
            $table->string('razon_social', 255);
            $table->string('tipo_documento', 60);
            $table->string('empresa_mandante', 20);
            $table->string('concepto', 255)->nullable();
            $table->unsignedInteger('cantidad')->nullable();

            $table->unsignedBigInteger('valor');
            $table->string('oc', 10);
            $table->string('impuesto', 10);
            $table->decimal('porcentaje_impuesto', 5, 2);
            $table->unsignedBigInteger('valor_impuesto');
            $table->unsignedBigInteger('valor_final_total');

            $table->timestamp('closed_at');

            $table->unique(['origen', 'origen_id'], 'courier_pagos_cerrados_origen_unique');
            $table->index(['courier_periodo_id', 'oc'], 'courier_pagos_cerrados_periodo_oc_index');
        });

        foreach (['courier_cierres', 'courier_pagos_cerrados'] as $tabla) {
            DB::unprepared("
                CREATE TRIGGER {$tabla}_sin_modificar BEFORE UPDATE ON {$tabla}
                FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un cierre de Courier no se puede modificar.'
            ");
            DB::unprepared("
                CREATE TRIGGER {$tabla}_sin_borrar BEFORE DELETE ON {$tabla}
                FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un cierre de Courier no se puede borrar.'
            ");
        }
    }

    public function down(): void
    {
        foreach (['courier_cierres', 'courier_pagos_cerrados'] as $tabla) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabla}_sin_modificar");
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabla}_sin_borrar");
        }

        Schema::dropIfExists('courier_pagos_cerrados');
        Schema::dropIfExists('courier_cierres');
    }
};
