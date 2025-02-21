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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('centro_costo')->nullable();
            $table->string('glosa')->nullable();
            $table->text('observacion')->nullable();
            $table->string('tipo_pago')->nullable();
            $table->string('forma_pago')->nullable();
            $table->decimal('pago_total', 15, 2)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('año')->nullable();
            $table->string('mes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
