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
        Schema::create('iata_codigos', function (Blueprint $table) {
            $table->id();
            $table->string('cod_iata');
            $table->string('cod_iata2');
            $table->timestamps();
        });

        Schema::table('comuna_clasificacion_operativa', function (Blueprint $table) {
            $table->foreignId('iata_id')
                ->nullable()
                ->after('provincia_id')
                ->constrained('iata_codigos');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iata_codigos');
    }
};
