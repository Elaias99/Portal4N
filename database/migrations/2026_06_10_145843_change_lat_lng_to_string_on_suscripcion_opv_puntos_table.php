<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suscripcion_opv_puntos', function (Blueprint $table) {
            $table->string('lat', 30)->nullable()->change();
            $table->string('lng', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('suscripcion_opv_puntos', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->change();
            $table->decimal('lng', 10, 7)->nullable()->change();
        });
    }
};