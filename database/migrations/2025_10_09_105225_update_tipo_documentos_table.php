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
                // 📋 Inserta los códigos oficiales del SII (solo documentos de ventas)
        DB::table('tipo_documentos')->insert([
            ['id' => 30,  'nombre' => 'Factura'],
            ['id' => 32,  'nombre' => 'Factura de ventas y servicios no afectos o exentos de IVA'],
            ['id' => 33,  'nombre' => 'Factura Electrónica'],
            ['id' => 34,  'nombre' => 'Factura No Afecta o Exenta Electrónica'],
            ['id' => 35,  'nombre' => 'Total operaciones del mes, con boleta (afecta)'],
            ['id' => 38,  'nombre' => 'Total operaciones del mes con boleta no afecta o exenta'],
            ['id' => 39,  'nombre' => 'Total operaciones del mes, con boleta electrónica'],
            ['id' => 40,  'nombre' => 'Liquidación Factura'],
            ['id' => 43,  'nombre' => 'Liquidación-Factura Electrónica'],
            ['id' => 45,  'nombre' => 'Factura de Compra'],
            ['id' => 46,  'nombre' => 'Factura de Compra Electrónica'],
            ['id' => 55,  'nombre' => 'Nota de Débito'],
            ['id' => 56,  'nombre' => 'Nota de Débito Electrónica'],
            ['id' => 60,  'nombre' => 'Nota de Crédito'],
            ['id' => 61,  'nombre' => 'Nota de Crédito Electrónica'],
            ['id' => 101, 'nombre' => 'Factura de Exportación'],
            ['id' => 102, 'nombre' => 'Factura de venta exenta a zona franca primaria'],
            ['id' => 103, 'nombre' => 'Liquidación'],
            ['id' => 104, 'nombre' => 'Nota de Débito de Exportación'],
            ['id' => 106, 'nombre' => 'Nota de Crédito de Exportación'],
            ['id' => 108, 'nombre' => 'SRF Solicitud Registro de Factura'],
            ['id' => 109, 'nombre' => 'Factura a Turista'],
            ['id' => 110, 'nombre' => 'Factura de Exportación Electrónica'],
            ['id' => 111, 'nombre' => 'Nota de Débito de Exportación Electrónica'],
            ['id' => 112, 'nombre' => 'Nota de Crédito de Exportación Electrónica'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            //
            DB::table('tipo_documentos')->truncate();
        });
    }
};
