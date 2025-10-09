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
        Schema::create('cruces', function (Blueprint $table) {
            $table->id();

            // Relación con el documento financiero principal
            $table->foreignId('documento_financiero_id')
                ->constrained('documentos_financieros')
                ->onDelete('cascade');

            // Datos del cruce
            $table->integer('monto')->default(0);
            $table->date('fecha_cruce');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cruces');
    }
};
