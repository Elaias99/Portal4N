<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Texto del correo de pre-facturas, uno por período.
         *
         * Lo escribe quien envía (fechas de emisión y de pago).
         * El saludo y la línea del adjunto siguen siendo automáticos.
         */
        Schema::create('suscripcion_correo_textos', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            $table->text('cuerpo');

            $table->timestamps();

            $table->unique(['anio', 'mes'], 'sus_correo_texto_periodo_uq');
        });

        /*
         * Septiembre 2026 queda con el texto que ya está en producción,
         * para que su envío siga igual después del despliegue.
         */
        DB::table('suscripcion_correo_textos')->insert([
            'anio' => 2026,
            'mes' => 9,
            'cuerpo' => 'Es indispensable que emita el documento correspondiente hasta el día '
                . 'martes 20/10, para que el pago sea realizado el día viernes 23/10. '
                . 'Si su documento es emitido fuera de plazo, el pago será realizado '
                . 'el día viernes 30/10.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_correo_textos');
    }
};
