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
        Schema::table('factories', function (Blueprint $table) {
            $table->string('estado_operacion', 20)
                ->nullable()
                ->after('monto_a_recibir')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropIndex(['estado_operacion']);
            $table->dropColumn('estado_operacion');
        });
    }
};