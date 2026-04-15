<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_consultas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tracking_almacenado_id')
                ->constrained('trackings_almacenados')
                ->cascadeOnDelete();

            $table->string('document_type', 10)->default('SO');

            $table->unsignedSmallInteger('latam_http_status')->nullable();
            $table->boolean('latam_respondio')->default(false);
            $table->boolean('html_recibido')->default(false);

            $table->boolean('parse_ok')->default(false);
            $table->boolean('estado_detectado')->default(false);
            $table->boolean('cambio_detectado')->nullable();

            $table->string('estado_resumen', 150)->nullable();
            $table->string('latest_event_code', 20)->nullable();
            $table->string('latest_event_time_raw', 100)->nullable();

            $table->string('estado_firma', 64)->nullable();
            $table->string('html_hash', 64)->nullable();

            $table->json('parsed_payload_json')->nullable();
            $table->mediumText('raw_html')->nullable();

            $table->string('parser_version', 50)->nullable();

            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('consultado_en')->nullable()->index();

            $table->timestamps();

            $table->index(['tracking_almacenado_id', 'consultado_en']);
            $table->index(['parse_ok']);
            $table->index(['cambio_detectado']);
            $table->index(['estado_firma']);
            $table->index(['html_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_consultas');
    }
};