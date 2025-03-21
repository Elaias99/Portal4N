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
        Schema::create('forma_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Modificar la tabla compras para agregar la clave foránea
        Schema::table('compras', function (Blueprint $table) {
            $table->unsignedBigInteger('forma_pago_id')->nullable()->after('tipo_pago_id');

            $table->foreign('forma_pago_id')->references('id')->on('forma_pago')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['forma_pago_id']);
            $table->dropColumn('forma_pago_id');
        });

        Schema::dropIfExists('forma_pago');
    }
};
