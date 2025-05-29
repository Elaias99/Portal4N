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
        Schema::create('producto_bases', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // código de barras (ej. 3017620425035)
            $table->string('nombre')->nullable(); // nombre del producto (opcional)
            $table->string('peso')->nullable();   // ej. "5kg"
            $table->string('altura')->nullable(); // ej. "10cm"
            $table->string('ancho')->nullable();  // ej. "20cm"
            $table->string('profundidad')->nullable(); // ej. "8cm"
            


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_bases');
    }
};
