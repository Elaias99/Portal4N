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
        Schema::table('cobranza_compras', function (Blueprint $table) {
            //

            // Nuevas columnas según el Excel
            $table->string('tipo')->nullable();
            $table->string('facturacion')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('zona')->nullable();
            $table->string('importancia')->nullable();
            $table->string('responsable')->nullable();
            $table->string('nombre_cuenta')->nullable();
            $table->string('rut_cuenta')->nullable();
            $table->string('numero_cuenta')->nullable();

            // Relaciones foráneas
            $table->foreignId('banco_id')
                  ->nullable()
                  ->constrained('bancos')
                  ->nullOnDelete();

            $table->foreignId('tipo_cuenta_id')
                  ->nullable()
                  ->constrained('tipo_cuentas')
                  ->nullOnDelete();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobranza_compras', function (Blueprint $table) {
            //

            $table->dropColumn([
                'tipo',
                'facturacion',
                'forma_pago',
                'zona',
                'importancia',
                'responsable',
                'nombre_cuenta',
                'rut_cuenta',
                'numero_cuenta',
            ]);

            $table->dropConstrainedForeignId('banco_id');
            $table->dropConstrainedForeignId('tipo_cuenta_id');

        });
    }
};
