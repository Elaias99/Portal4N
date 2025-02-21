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
        Schema::create('tasa_afps', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('id_afp');
            $table->decimal('tasa_cotizacion', 5, 2);
            $table->decimal('tasa_sis', 5, 2);

            $table->timestamps();

            $table->foreign('id_afp')->references('id')->on('a_f_p_s')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasa_afps');
    }
};
