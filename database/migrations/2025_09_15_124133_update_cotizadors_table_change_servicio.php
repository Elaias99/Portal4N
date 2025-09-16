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
        //
        Schema::table('cotizadors', function (Blueprint $table) {
            // Quitamos la columna enum existente
            $table->dropColumn('servicio');

            // Agregamos la relación con servicios
            $table->foreignId('servicio_id')
                  ->constrained('servicios')
                  ->after('nombre_cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('cotizadors', function (Blueprint $table) {
            // Eliminamos la relación con servicios
            $table->dropConstrainedForeignId('servicio_id');

            // Volvemos a crear el enum original
            $table->enum('servicio', ['Transporte', 'Courier', 'Almacenaje'])
                  ->default('Transporte')
                  ->after('nombre_cliente');
        });

        
    }
};
