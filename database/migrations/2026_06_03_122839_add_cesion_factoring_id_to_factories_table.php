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
            $table->foreignId('cesion_factoring_id')
                ->nullable()
                ->after('documento_financiero_id')
                ->constrained('cesiones_factoring')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cesion_factoring_id');
        });
    }
};