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
        Schema::table('compras', function (Blueprint $table) {
            
            $table->date('fecha_documento')->nullable(); // Fecha del documento
            $table->string('numero_documento')->nullable(); // Número del documento
            $table->string('oc')->nullable(); // Orden de compra (O.C)
            $table->string('archivo_oc')->nullable(); // Ruta para adjuntar O.C
            $table->string('archivo_documento')->nullable(); // Ruta para adjuntar documento
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            //
            $table->dropColumn(['fecha_documento', 'numero_documento', 'oc', 'archivo_oc', 'archivo_documento']);
        });
    }
};
