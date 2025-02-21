<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade'); // Relación con proveedores
            $table->string('centro_costo');
            $table->text('glosa')->nullable();
            $table->string('pagador');
            $table->enum('status', ['Pendiente', 'Pagado', 'Abonado', 'No Pagar'])->default('Pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
