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
        Schema::table('documentos_compras', function (Blueprint $table) {
            //
            // 🔗 Relación con cobranzas
            $table->foreignId('cobranza_id')
                ->nullable()
                ->constrained('cobranzas')
                ->onDelete('set null');


            $table->string('status_original')->nullable()->after('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_compras', function (Blueprint $table) {
            //
            $table->dropForeign(['cobranza_id']);
            $table->dropColumn(['cobranza_id', 'status_original']);
        });
    }
};
