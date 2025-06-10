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
        Schema::create('comuna_clasificacion_operativa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comuna_id')->constrained('comunas')->onDelete('cascade');
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->foreignId('tipo_zona_id')->nullable()->constrained('tipos_zona')->nullOnDelete();
            $table->foreignId('subzona_id')->nullable()->constrained('subzonas')->nullOnDelete();
            $table->string('comuna_matriz')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comuna_clasificacion_operativa');
    }
};
