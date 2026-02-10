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
        // Obtener cobranzas usadas solo en documentos_compras (no presentes en documentos_financieros)
        $cobranzasSoloCompras = DB::table('cobranzas')
            ->whereIn('id', function ($query) {
                $query->select('cobranza_id')
                    ->from('documentos_compras')
                    ->whereNotIn('cobranza_id', function ($sub) {
                        $sub->select('cobranza_id')
                            ->from('documentos_financieros')
                            ->whereNotNull('cobranza_id');
                    });
            })
            ->get();

        // Insertar cada registro en la nueva tabla cobranza_compras
        foreach ($cobranzasSoloCompras as $cobranza) {
            $nuevaId = DB::table('cobranza_compras')->insertGetId([
                'rut_cliente'   => $cobranza->rut_cliente,
                'razon_social'  => $cobranza->razon_social,
                'servicio'      => $cobranza->servicio,
                'creditos'      => $cobranza->creditos,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Actualizar documentos_compras para que apunten a la nueva cobranza
            DB::table('documentos_compras')
                ->where('cobranza_id', $cobranza->id)
                ->update(['cobranza_compra_id' => $nuevaId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los cambios (solo si es seguro hacerlo)
        // Volver a dejar los documentos apuntando a la tabla cobranzas original
        $cobranzasCompras = DB::table('cobranza_compras')->get();

        foreach ($cobranzasCompras as $compra) {
            $original = DB::table('cobranzas')
                ->where('rut_cliente', $compra->rut_cliente)
                ->first();

            if ($original) {
                DB::table('documentos_compras')
                    ->where('cobranza_compra_id', $compra->id)
                    ->update(['cobranza_id' => $original->id]);
            }
        }

        // Luego eliminar los registros copiados
        DB::table('cobranza_compras')->truncate();
    }
};
