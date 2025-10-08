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
        Schema::table('documentos_financieros', function (Blueprint $table) {

             // Campo para almacenar el estado original del documento al momento de la importación
            $table->string('status_original', 50)
                ->nullable()
                ->after('status'); // lo colocamos justo después de 'status' por lógica
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_financieros', function (Blueprint $table) {
            //
            $table->dropColumn('status_original');
        });
    }
};
