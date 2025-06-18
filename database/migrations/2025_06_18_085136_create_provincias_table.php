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
        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::table('comuna_clasificacion_operativa', function (Blueprint $table) {
            $table->foreignId('provincia_id')
                ->nullable()
                ->after('cobertura_id')
                ->constrained('provincias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provincias');
    }
};
