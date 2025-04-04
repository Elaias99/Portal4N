<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plazo_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Insertar registros base
        DB::table('plazo_pago')->insert([
            ['nombre' => 'Contado', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Quincena', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => '30 Días', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => '45 Días', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => '60 Días', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Agregar columna a compras
        Schema::table('compras', function (Blueprint $table) {
            $table->unsignedBigInteger('plazo_pago_id')->nullable()->after('tipo_pago');

            $table->foreign('plazo_pago_id')
                ->references('id')
                ->on('plazo_pago')
                ->nullOnDelete(); // o cascadeOnDelete() si deseas eliminar compras al eliminar el plazo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['plazo_pago_id']);
            $table->dropColumn('plazo_pago_id');
        });

        Schema::dropIfExists('plazos_pago');
    }
};
