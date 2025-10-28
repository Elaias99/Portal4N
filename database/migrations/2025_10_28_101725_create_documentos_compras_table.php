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
        Schema::create('documentos_compras', function (Blueprint $table) {
            $table->id();


            // Relaciones
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('tipo_documento_id')->constrained('tipo_documentos')->onDelete('restrict');

            // Datos del archivo RCV_COMPRAS
            $table->integer('nro')->nullable();
            $table->integer('tipo_doc')->nullable();
            $table->string('tipo_compra', 100)->nullable();
            $table->string('rut_proveedor', 20)->nullable();
            $table->string('razon_social', 255)->nullable();
            $table->string('folio', 50)->nullable();

            $table->dateTime('fecha_docto')->nullable();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->dateTime('fecha_acuse')->nullable();

            $table->bigInteger('monto_exento')->default(0);
            $table->bigInteger('monto_neto')->default(0);
            $table->bigInteger('monto_iva_recuperable')->default(0);
            $table->bigInteger('monto_iva_no_recuperable')->default(0);
            $table->string('codigo_iva_no_rec', 50)->nullable();
            $table->bigInteger('monto_total')->default(0);
            $table->bigInteger('monto_neto_activo_fijo')->default(0);
            $table->bigInteger('iva_activo_fijo')->default(0);
            $table->bigInteger('iva_uso_comun')->default(0);
            $table->bigInteger('impto_sin_derecho_credito')->default(0);
            $table->bigInteger('iva_no_retenido')->default(0);

            $table->bigInteger('tabacos_puros')->default(0);
            $table->bigInteger('tabacos_cigarrillos')->default(0);
            $table->bigInteger('tabacos_elaborados')->default(0);

            $table->string('nce_nde_sobre_fact_compra', 100)->nullable();
            $table->string('codigo_otro_impuesto', 50)->nullable();
            $table->bigInteger('valor_otro_impuesto')->default(0);
            $table->decimal('tasa_otro_impuesto', 10, 2)->nullable();

            // Campos de control interno
            $table->string('estado')->nullable(); // Pendiente, Pagado, etc.
            $table->date('fecha_vencimiento')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_compras');
    }
};
