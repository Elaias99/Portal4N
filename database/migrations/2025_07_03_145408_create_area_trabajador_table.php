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
        Schema::create('area_trabajador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadors')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['trabajador_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_trabajador');
    }
};
