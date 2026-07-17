<?php

namespace App\Services\Suscripciones;

use App\Models\SuscripcionLiquidacionDetalle;

class SuscripcionPrefacturaOcService
{



    public function generarOC(int $anio, int $mes, int $suscripcionProveedorId): string
    {
        $proveedoresOrdenados = SuscripcionLiquidacionDetalle::query()
            ->join('suscripcion_asignaciones', 'suscripcion_liquidacion_detalles.suscripcion_asignacion_id', '=', 'suscripcion_asignaciones.id')
            ->join('suscripcion_proveedores', 'suscripcion_asignaciones.suscripcion_proveedor_id', '=', 'suscripcion_proveedores.id')
            ->join('cobranza_compras', 'suscripcion_proveedores.cobranza_compra_id', '=', 'cobranza_compras.id')
            ->where('suscripcion_liquidacion_detalles.anio', $anio)
            ->where('suscripcion_liquidacion_detalles.mes', $mes)
            ->select(
                'suscripcion_proveedores.id',
                'cobranza_compras.razon_social'
            )
            ->distinct()
            ->orderByRaw('UPPER(TRIM(cobranza_compras.razon_social)) ASC')
            ->orderBy('suscripcion_proveedores.id')
            ->get()
            ->values();

        $indice = $proveedoresOrdenados->search(function ($proveedor) use ($suscripcionProveedorId) {
            return (int) $proveedor->id === (int) $suscripcionProveedorId;
        });

        if ($indice === false) {
            $correlativo = '00';
        } else {
            $correlativo = str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT);
        }

        return (string) $anio
            . str_pad((string) $mes, 2, '0', STR_PAD_LEFT)
            . $correlativo;
    }






    
}



