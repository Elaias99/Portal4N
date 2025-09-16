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
        Schema::create('maquilados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cotizador_id')
                  ->constrained('cotizadors')
                  ->onDelete('cascade'); // si eliminas la cotización, se borra también el maquilado

            $table->enum('insumo', ['proveedor', 'cliente'])->nullable();
            $table->string('detalle_insumo')->nullable();
            $table->integer('unidades')->unsigned();
            $table->string('tipo_maquila')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquilados');
    }
};
