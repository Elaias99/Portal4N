<?php

namespace App\Services\Suscripciones;

use App\Models\SuscripcionLiquidacionDetalle;
use Barryvdh\DomPDF\Facade\Pdf;

class SuscripcionPrefacturaPdfService
{
    public function __construct(
        private SuscripcionLiquidacionResumenService $resumenService,
        private SuscripcionPrefacturaAgrupacionService $agrupacionService,
        private SuscripcionPrefacturaOcService $ocService,
        private SuscripcionAjusteMensualService $ajusteMensualService
    ) {
    }

    public function generarDesdeDetalle(
        SuscripcionLiquidacionDetalle $detalle
    ): array {
        $detalle->load([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
            'asignacion.cantidadesMensuales',
        ]);

        /*
         * La pre-factura debe utilizar al proveedor efectivo del período.
         *
         * Este proveedor puede ser:
         * - el proveedor habitual de la asignación;
         * - o el proveedor definido por un ajuste mensual de facturación.
         */
        $proveedor = $this->ajusteMensualService
            ->proveedorFacturacionParaDetalle($detalle);

        if (!$proveedor?->id) {
            throw new \RuntimeException(
                'No se encontró el proveedor efectivo de la pre-factura.'
            );
        }

        /*
         * Asegura que los datos bancarios correspondan al proveedor efectivo,
         * especialmente cuando proviene de un ajuste mensual.
         */
        $proveedor->loadMissing([
            'cobranzaCompra.banco',
            'cobranzaCompra.tipoCuenta',
        ]);

        $suscripcionProveedorId = (int) $proveedor->id;
        $cobranzaCompra = $proveedor->cobranzaCompra;

        $grupoPrefactura = $this->agrupacionService
            ->grupoDesdeDetalle($detalle);

        $grupoPrefacturaLabel = $this->agrupacionService
            ->etiquetaGrupo($grupoPrefactura);

        /*
         * Reúne solamente las líneas del mismo:
         * - proveedor efectivo;
         * - año;
         * - mes;
         * - grupo de pre-factura.
         */
        $detallesProveedor = SuscripcionLiquidacionDetalle::with([
            'asignacion.suscripcionProveedor.cobranzaCompra',
            'asignacion.suscripcionProveedor.cobranzaCompra.banco',
            'asignacion.suscripcionProveedor.cobranzaCompra.tipoCuenta',
            'asignacion.transportista',
            'asignacion.opvPuntos',
            'asignacion.cantidadesMensuales',
        ])
            ->where('anio', $detalle->anio)
            ->where('mes', $detalle->mes)
            ->orderBy('codigo')
            ->get()
            ->filter(function ($item) use (
                $suscripcionProveedorId,
                $grupoPrefactura
            ) {
                $proveedorItem = $this->ajusteMensualService
                    ->proveedorFacturacionParaDetalle($item);

                if (
                    (int) $proveedorItem?->id
                    !== $suscripcionProveedorId
                ) {
                    return false;
                }

                $grupoItem = $this->agrupacionService
                    ->grupoDesdeDetalle($item);

                return $this->agrupacionService
                    ->claveGrupo($grupoItem)
                    === $this->agrupacionService
                        ->claveGrupo($grupoPrefactura);
            })
            ->values();

        if ($detallesProveedor->isEmpty()) {
            throw new \RuntimeException(
                'No se encontraron detalles para generar la pre-factura.'
            );
        }

        $calculosDetalle = $this->resumenService
            ->calcularPorDetalles($detallesProveedor);

        $totalBruto = $detallesProveedor->sum('total');
        $totalImpuesto = $calculosDetalle->sum('total_impuesto');
        $totalLiquido = $calculosDetalle->sum('liquido');

        $ocPrefactura = $this->ocService->generarOC(
            (int) $detalle->anio,
            (int) $detalle->mes,
            $suscripcionProveedorId
        );

        $meses = $this->meses();

        $nombreArchivo = $this->nombreArchivo(
            $proveedor,
            $cobranzaCompra,
            (int) $detalle->anio,
            (int) $detalle->mes,
            $grupoPrefactura,
            $grupoPrefacturaLabel
        );

        $pdf = Pdf::loadView(
            'suscripciones.liquidacion_detalles.pdf',
            [
                'detalle' => $detalle,
                'detallesProveedor' => $detallesProveedor,
                'calculosDetalle' => $calculosDetalle,
                'proveedor' => $proveedor,
                'cobranzaCompra' => $cobranzaCompra,
                'totalBruto' => $totalBruto,
                'totalImpuesto' => $totalImpuesto,
                'totalLiquido' => $totalLiquido,
                'meses' => $meses,
                'grupoPrefactura' => $grupoPrefactura,
                'grupoPrefacturaLabel' => $grupoPrefacturaLabel,
                'ocPrefactura' => $ocPrefactura,
            ]
        )->setPaper('letter', 'portrait');

        return [
            'pdf' => $pdf,
            'nombre_archivo' => $nombreArchivo,

            'detalle' => $detalle,
            'detalles' => $detallesProveedor,

            'proveedor' => $proveedor,
            'cobranza_compra' => $cobranzaCompra,

            /*
             * Este es el correo real registrado en la base.
             * Durante las pruebas todavía no se utilizará como destinatario.
             */
            'correo_proveedor' => trim(
                (string) ($proveedor->correo ?? '')
            ),

            'anio' => (int) $detalle->anio,
            'mes' => (int) $detalle->mes,
            'mes_nombre' => $meses[(int) $detalle->mes]
                ?? (string) $detalle->mes,

            'grupo_prefactura' => $grupoPrefactura,
            'grupo_prefactura_label' => $grupoPrefacturaLabel,

            'oc' => $ocPrefactura,

            'total_bruto' => $totalBruto,
            'total_impuesto' => $totalImpuesto,
            'total_liquido' => $totalLiquido,
        ];
    }

    private function nombreArchivo(
        $proveedor,
        $cobranzaCompra,
        int $anio,
        int $mes,
        ?string $grupoPrefactura,
        string $grupoPrefacturaLabel
    ): string {
        $nombreProveedor = $cobranzaCompra?->razon_social
            ?? 'Proveedor';

        $tipo = $proveedor?->tipo ?? 'DOC';

        $nombreArchivoProveedor = str_replace(
            ' ',
            '_',
            preg_replace(
                '/[^A-Za-z0-9\s]/',
                '',
                $nombreProveedor
            )
        );

        $grupoArchivo = '';

        if ($grupoPrefactura !== null) {
            $grupoArchivo = '_' . str_replace(
                ' ',
                '_',
                preg_replace(
                    '/[^A-Za-z0-9\s._-]/',
                    '',
                    $grupoPrefacturaLabel
                )
            );
        }

        return "PreFactura_Susc_{$tipo}_"
            . "{$nombreArchivoProveedor}"
            . "{$grupoArchivo}_"
            . "{$anio}_{$mes}.pdf";
    }

    private function meses(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }
}