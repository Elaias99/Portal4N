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
        Schema::create('tipos_maquila', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // Ahora ajustamos la tabla maquilados para que apunte a tipo_maquila
        Schema::table('maquilados', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_maquila_id')->nullable()->after('insumo');
            $table->foreign('tipo_maquila_id')
                  ->references('id')
                  ->on('tipos_maquila')
                  ->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maquilados', function (Blueprint $table) {
            $table->dropForeign(['tipo_maquila_id']);
            $table->dropColumn('tipo_maquila_id');
        });

        Schema::dropIfExists('tipos_maquila');
    }
};
