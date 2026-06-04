<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripcion_proveedores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cobranza_compra_id')
                ->constrained('cobranza_compras')
                ->restrictOnDelete();

            $table->string('tipo', 50)->nullable(); 
            // BOLETA, FACTURA, DOCUMENTO

            $table->string('detalle_documento', 100)->nullable(); 
            // BRUTO, NETO, etc.

            $table->string('detalle_impuesto', 100)->nullable(); 
            // RETENCION, IMPUESTO, etc.

            $table->string('final', 100)->nullable(); 
            // LIQUIDO A PAGAR, TOTAL, etc.

            $table->timestamps();

            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_proveedores');
    }
};