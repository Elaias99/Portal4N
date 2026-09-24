<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bsales', function (Blueprint $table) {
            $table->id();

            // Identidad del registro local.
            $table->char('identificador', 64)->unique();

            // Permite buscar grupos con el mismo contenido.
            // No es único: dos viajes distintos podrían tener datos iguales.
            $table->char('huella_datos', 64)->index();

            // Usuario que realizó la importación.
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Archivo y contenido original del grupo.
            $table->string('archivo_origen');
            $table->json('datos_grupo');

            // Destino revisado por el usuario.
            $table->string('comuna_destino', 100)->nullable();
            $table->string('ciudad_destino', 100)->nullable();

            // Cuenta y configuración utilizadas.
            $table->string('ambiente', 20)->default('production');
            $table->string('empresa_bsale_id', 32)->default('101346');
            $table->unsignedInteger('document_type_id')->default(7);
            $table->boolean('declare_sii')->default(false);

            // pendiente, enviando, generada, error o incierta.
            $table->string('estado', 20)
                ->default('pendiente')
                ->index();

            // JSON enviado: guardar los datos, nunca el token.
            $table->json('payload_enviado')->nullable();

            // Resultado de Bsale.
            $table->string('bsale_shipping_id', 64)->nullable();
            $table->string('bsale_document_id', 64)->nullable();
            $table->string('numero_guia', 64)->nullable();

            $table->text('url_pdf')->nullable();
            $table->text('url_vista')->nullable();

            $table->unsignedSmallInteger('estado_http')->nullable();
            $table->json('respuesta_bsale')->nullable();
            $table->text('mensaje_error')->nullable();

            // Seguimiento de la generación.
            $table->timestamp('envio_iniciado_at')->nullable();
            $table->timestamp('respuesta_recibida_at')->nullable();
            $table->timestamp('generada_at')->nullable();

            $table->timestamps();

            // Un mismo documento remoto no debe asociarse a dos registros.
            $table->unique(
                ['ambiente', 'empresa_bsale_id', 'bsale_document_id'],
                'bsales_documento_remoto_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bsales');
    }
};