<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_bultos', function (Blueprint $table) {
            $table->string('destinatario_telefono', 255)
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        /*
         * No se reduce a 50 caracteres: podría cortar teléfonos crudos
         * de Geolice que ya fueron importados.
         */
    }
};