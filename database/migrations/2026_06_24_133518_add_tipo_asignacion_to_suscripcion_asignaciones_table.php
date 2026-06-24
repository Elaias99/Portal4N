<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suscripcion_asignaciones', function (Blueprint $table) {
            $table->string('tipo_asignacion', 30)
                ->default('RUTA')
                ->after('generar_automaticamente')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('suscripcion_asignaciones', function (Blueprint $table) {
            $table->dropIndex(['tipo_asignacion']);
            $table->dropColumn('tipo_asignacion');
        });
    }
};
