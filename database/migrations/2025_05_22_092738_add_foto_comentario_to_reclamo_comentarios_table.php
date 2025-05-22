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
        Schema::table('reclamo_comentarios', function (Blueprint $table) {
            //
            $table->string('foto_comentario')->nullable()->after('comentario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamo_comentarios', function (Blueprint $table) {
            //
            $table->string('foto_comentario')->nullable()->after('comentario');
        });
    }
};
