<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_estados_actuales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tracking_almacenado_id')
                ->unique()
                ->constrained('trackings_almacenados')
                ->cascadeOnDelete();

            $table->string('document_type', 10)->default('SO');

            $table->boolean('tiene_estado_valido')->default(false);

            $table->string('estado_resumen', 150)->nullable();

            $table->string('origen', 10)->nullable();
            $table->string('destino_latam', 10)->nullable();

            $table->string('arrival_on_or_before_raw', 100)->nullable();

            $table->string('product', 100)->nullable();
            $table->string('commodity', 150)->nullable();

            $table->unsignedInteger('pieces')->nullable();
            $table->decimal('weight', 10, 2)->nullable();

            $table->string('latest_event_code', 20)->nullable();
            $table->string('latest_event_description', 150)->nullable();
            $table->string('latest_event_station', 20)->nullable();
            $table->string('latest_event_time_raw', 100)->nullable();

            $table->string('latest_leg_flight', 50)->nullable();
            $table->string('latest_leg_etd_raw', 100)->nullable();
            $table->string('latest_leg_eta_raw', 100)->nullable();

            $table->json('parsed_payload_json')->nullable();
            $table->json('hidden_metadata_json')->nullable();
            $table->json('irregularities_json')->nullable();
            $table->json('export_options_json')->nullable();

            $table->string('estado_firma', 64)->nullable();
            $table->string('html_hash', 64)->nullable();
            $table->string('parser_version', 50)->nullable();

            $table->timestamp('ultima_consulta_at')->nullable()->index();
            $table->timestamp('ultima_consulta_exitosa_at')->nullable();
            $table->timestamp('ultimo_cambio_at')->nullable();

            $table->string('ultimo_error_code', 100)->nullable();
            $table->text('ultimo_error_message')->nullable();

            $table->timestamps();

            $table->index(['tiene_estado_valido']);
            $table->index(['latest_event_code']);
            $table->index(['estado_firma']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_estados_actuales');
    }
};