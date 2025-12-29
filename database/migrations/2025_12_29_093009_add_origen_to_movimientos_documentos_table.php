<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_documentos', function (Blueprint $table) {

            // Relación polimórfica al evento origen
            $table->nullableMorphs('origen');

            /*
             * Esto crea:
             * - origen_id (BIGINT, nullable)
             * - origen_type (VARCHAR, nullable)
             *
             * Ejemplos:
             * origen_type = App\Models\Abono
             * origen_id   = 15
             */
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_documentos', function (Blueprint $table) {
            $table->dropMorphs('origen');
        });
    }
};
