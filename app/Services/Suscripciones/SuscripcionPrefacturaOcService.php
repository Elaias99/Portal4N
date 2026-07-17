<?php

namespace App\Services\Suscripciones;

use App\Models\SuscripcionLiquidacionDetalle;

class SuscripcionPrefacturaOcService
{



    public function generarOC(
        int $anio,
        int $mes,
        int $suscripcionProveedorId
    ): string {
        $proveedoresOrdenados = SuscripcionLiquidacionDetalle::query()
            ->from('suscripcion_liquidacion_detalles as sld')

            ->join(
                'suscripcion_asignaciones as sa',
                'sld.suscripcion_asignacion_id',
                '=',
                'sa.id'
            )

            ->leftJoin(
                'suscripcion_ajustes_mensuales as sam',
                function ($join) {
                    $join
                        ->on(
                            'sam.suscripcion_asignacion_id',
                            '=',
                            'sa.id'
                        )
                        ->on('sam.anio', '=', 'sld.anio')
                        ->on('sam.mes', '=', 'sld.mes')
                        ->where('sam.activo', 1);
                }
            )

            ->join(
                'suscripcion_proveedores as sp_base',
                'sa.suscripcion_proveedor_id',
                '=',
                'sp_base.id'
            )

            ->join(
                'cobranza_compras as cc_base',
                'sp_base.cobranza_compra_id',
                '=',
                'cc_base.id'
            )

            ->leftJoin(
                'suscripcion_proveedores as sp_efectivo',
                'sam.suscripcion_proveedor_facturacion_id',
                '=',
                'sp_efectivo.id'
            )

            ->leftJoin(
                'cobranza_compras as cc_efectiva',
                'sp_efectivo.cobranza_compra_id',
                '=',
                'cc_efectiva.id'
            )

            ->where('sld.anio', $anio)
            ->where('sld.mes', $mes)

            ->selectRaw(
                'COALESCE(sp_efectivo.id, sp_base.id) AS id'
            )

            ->selectRaw(
                'COALESCE(
                    cc_efectiva.razon_social,
                    cc_base.razon_social
                ) AS razon_social'
            )

            ->groupByRaw(
                'COALESCE(sp_efectivo.id, sp_base.id),
                COALESCE(
                    cc_efectiva.razon_social,
                    cc_base.razon_social
                )'
            )

            ->orderByRaw(
                'UPPER(TRIM(
                    COALESCE(
                        cc_efectiva.razon_social,
                        cc_base.razon_social
                    )
                )) ASC'
            )

            ->orderByRaw(
                'COALESCE(sp_efectivo.id, sp_base.id) ASC'
            )

            ->get()
            ->values();

        $indice = $proveedoresOrdenados->search(
            function ($proveedor) use ($suscripcionProveedorId) {
                return (int) $proveedor->id
                    === (int) $suscripcionProveedorId;
            }
        );

        $correlativo = $indice === false
            ? '00'
            : str_pad(
                (string) ($indice + 1),
                2,
                '0',
                STR_PAD_LEFT
            );

        return (string) $anio
            . str_pad((string) $mes, 2, '0', STR_PAD_LEFT)
            . $correlativo;
    }






    
}



