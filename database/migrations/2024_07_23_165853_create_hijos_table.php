<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hijos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('genero');
            $table->string('parentesco');
            $table->date('fecha_nacimiento');
            $table->integer('edad');
            $table->unsignedBigInteger('trabajador_id');
            $table->foreign('trabajador_id')->references('id')->on('trabajadors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hijos');
    }
};

