<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasColumn('suscripcion_proveedores', 'correo')
            || !Schema::hasColumn('cobranza_compras', 'correo_suscripciones')
        ) {
            return;
        }

        $correosPorProveedor = DB::table('suscripcion_proveedores')
            ->whereNotNull('correo')
            ->orderBy('id')
            ->get(['cobranza_compra_id', 'correo'])
            ->groupBy('cobranza_compra_id');

        foreach ($correosPorProveedor as $cobranzaCompraId => $registros) {
            $correosValidos = $registros
                ->map(fn ($registro) => trim((string) $registro->correo))
                ->filter(fn ($correo) => $correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL))
                ->unique(fn ($correo) => mb_strtolower($correo))
                ->values();

            if ($correosValidos->count() !== 1) {
                continue;
            }

            DB::table('cobranza_compras')
                ->where('id', $cobranzaCompraId)
                ->where(function ($query) {
                    $query->whereNull('correo_suscripciones')
                        ->orWhereRaw("TRIM(correo_suscripciones) = ''");
                })
                ->update([
                    'correo_suscripciones' => $correosValidos->first(),
                ]);
        }
    }

    public function down(): void
    {
        if (
            !Schema::hasColumn('suscripcion_proveedores', 'correo')
            || !Schema::hasColumn('cobranza_compras', 'correo_suscripciones')
        ) {
            return;
        }

        $correosPorProveedor = DB::table('suscripcion_proveedores')
            ->whereNotNull('correo')
            ->orderBy('id')
            ->get(['cobranza_compra_id', 'correo'])
            ->groupBy('cobranza_compra_id');

        foreach ($correosPorProveedor as $cobranzaCompraId => $registros) {
            $correosValidos = $registros
                ->map(fn ($registro) => trim((string) $registro->correo))
                ->filter(fn ($correo) => $correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL))
                ->unique(fn ($correo) => mb_strtolower($correo))
                ->values();

            if ($correosValidos->count() !== 1) {
                continue;
            }

            DB::table('cobranza_compras')
                ->where('id', $cobranzaCompraId)
                ->where('correo_suscripciones', $correosValidos->first())
                ->update([
                    'correo_suscripciones' => null,
                ]);
        }
    }
};
