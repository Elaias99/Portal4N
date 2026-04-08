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
        Schema::create('trackings_almacenados', function (Blueprint $table) {
            $table->id();
            $table->string('prefijo', 3);
            $table->string('codigo_tracking', 8);
            $table->date('fecha_proceso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackings_almacenados');
    }
};
