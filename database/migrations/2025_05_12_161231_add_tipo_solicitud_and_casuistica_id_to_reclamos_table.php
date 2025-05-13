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
        Schema::table('reclamos', function (Blueprint $table) {
            //
            $table->string('tipo_solicitud')->nullable()->after('estado');
            $table->foreignId('casuistica_id')->nullable()->constrained('casuisticas')->nullOnDelete()->after('tipo_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            //
            $table->dropForeign(['casuistica_id']);
            $table->dropColumn(['tipo_solicitud', 'casuistica_id']);
        });
    }
};
