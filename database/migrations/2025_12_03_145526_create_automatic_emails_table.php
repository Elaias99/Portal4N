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
        Schema::create('automatic_emails', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');                // Nombre del correo
            $table->string('asunto');                // Asunto editable
            $table->longText('cuerpo_html');         // HTML completo del correo
            $table->text('destinatarios');
            
            $table->enum('tipo_frecuencia', [
                'diario',
                'semanal',
                'mensual'
            ]);

            $table->time('hora_envio')->nullable();
            $table->json('dias_semana')->nullable();

            // Activo/inactivo
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_emails');
    }
};
