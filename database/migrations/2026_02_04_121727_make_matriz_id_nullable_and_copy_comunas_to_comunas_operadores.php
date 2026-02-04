<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // 1) Hacer matriz_id nullable
        Schema::table('comunas_operadores', function (Blueprint $table) {
            $table->unsignedBigInteger('matriz_id')->nullable()->change();
        });

        // 2) Copiar comunas
        DB::table('comunas_operadores')->insert(
            DB::table('comunas')
                ->select('Nombre as nombre_comuna')
                ->get()
                ->map(fn ($row) => [
                    'nombre_comuna' => $row->nombre_comuna,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ])
                ->toArray()
        );
    }

    public function down(): void
    {
        // Vaciar comunas_operadores
        DB::table('comunas_operadores')->truncate();

        // Volver matriz_id a NOT NULL
        Schema::table('comunas_operadores', function (Blueprint $table) {
            $table->unsignedBigInteger('matriz_id')->nullable(false)->change();
        });
    }
};
