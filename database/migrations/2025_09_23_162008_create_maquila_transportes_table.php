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
        Schema::create('maquila_transportes', function (Blueprint $table) {
            $table->id();

            // Relación con maquilado
            $table->foreignId('maquilado_id')->constrained('maquilados')->onDelete('cascade');

            // Relación con catálogo transportes
            $table->foreignId('transporte_id')->nullable()->constrained('transportes')->nullOnDelete();

            // Campos de dirección y coordenadas
            $table->string('origen')->nullable();
            $table->decimal('origen_lat', 11, 7)->nullable();
            $table->decimal('origen_lon', 11, 7)->nullable();

            $table->string('destino')->nullable();
            $table->decimal('destino_lat', 11, 7)->nullable();
            $table->decimal('destino_lon', 11, 7)->nullable();

            // Resultados ORS
            $table->decimal('distancia_km', 10, 2)->nullable();
            

            // Pionetas
            $table->boolean('lleva_pioneta')->default(false);
            $table->unsignedInteger('cantidad_pionetas')->nullable();
            $table->string('jornada_pioneta')->nullable();

            // Con carga
            $table->boolean('con_carga')->default(false);



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquila_transportes');
    }
};
