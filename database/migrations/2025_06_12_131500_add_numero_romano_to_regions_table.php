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
    public function up()
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->string('NumeroRomano', 10)->nullable()->after('Numero');
        });

        // Poblamos la columna con valores romanos
        DB::table('regions')->update([
            'NumeroRomano' => DB::raw("
                CASE Numero
                    WHEN '1' THEN 'I'
                    WHEN '2' THEN 'II'
                    WHEN '3' THEN 'III'
                    WHEN '4' THEN 'IV'
                    WHEN '5' THEN 'V'
                    WHEN '6' THEN 'VI'
                    WHEN '7' THEN 'VII'
                    WHEN '8' THEN 'VIII'
                    WHEN '9' THEN 'IX'
                    WHEN '10' THEN 'X'
                    WHEN '11' THEN 'XI'
                    WHEN '12' THEN 'XII'
                    WHEN '13' THEN 'XIII'
                    WHEN '14' THEN 'XIV'
                    WHEN '15' THEN 'XV'
                    WHEN '16' THEN 'XVI'
                END
            ")
        ]);
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            //
            $table->dropColumn('NumeroRomano');

            
        });
    }
};
