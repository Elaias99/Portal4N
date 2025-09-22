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
        Schema::create('maquilado_insumos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('maquilado_id');
            $table->string('detalle');
            $table->integer('cantidad');
            $table->decimal('precio', 12, 2);
            $table->decimal('subtotal', 14, 2);

            $table->timestamps();

            // relación con maquilados
            $table->foreign('maquilado_id')
                  ->references('id')
                  ->on('maquilados')
                  ->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquilado_insumos');
    }
};
