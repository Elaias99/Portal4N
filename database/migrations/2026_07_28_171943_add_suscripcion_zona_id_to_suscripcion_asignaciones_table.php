<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suscripcion_asignaciones', function (Blueprint $table) {
            $table->foreignId('suscripcion_zona_id')
                ->nullable()
                ->after('suscripcion_transportista_id')
                ->constrained('suscripcion_zonas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('suscripcion_asignaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suscripcion_zona_id');
        });
    }
};