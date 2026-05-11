<?php

namespace App\Services\Honorarios;

use App\Models\CobranzaCompra;
use App\Models\HonorarioMensualRec;
use App\Models\HonorarioMensualRecTotal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HonorarioMensualStoreService
{
    public function execute(array $data): bool
    {
        $tipoBoleta = $data['tipo_boleta'] ?? 'Boleta Honorario';
        $meta       = $data['meta'] ?? [];
        $registros  = $data['registros'] ?? [];
        $empresaId  = $data['empresa']['id'] ?? null;

        if (!$empresaId || empty($registros)) {
            return false;
        }

        $anioDocumento = $meta['anio'] ?? null;
        $mesDocumento  = $meta['mes'] ?? null;

        foreach ($registros as $r) {
            $rutEmisor = $r['rut_emisor'] ?? null;

            $rutContribuyenteDocumento = $r['rut_contribuyente']
                ?? $meta['rut_contribuyente']
                ?? null;

            $razonSocialDocumento = $r['razon_social']
                ?? $meta['razon_social']
                ?? null;

            $anioDocumento = $meta['anio'] ?? null;
            $mesDocumento  = $meta['mes'] ?? null;

            if (
                empty($rutContribuyenteDocumento) ||
                empty($razonSocialDocumento) ||
                empty($anioDocumento) ||
                empty($mesDocumento)
            ) {
                continue;
            }

            // Buscar proveedor en cobranza_compras
            $cobranza = $rutEmisor
                ? CobranzaCompra::where('rut_cliente', $rutEmisor)->first()
                : null;

            $formaPagoNormalizada = mb_strtoupper(
                trim((string) ($cobranza->forma_pago ?? ''))
            );

            $esPagoAutomatico = in_array($formaPagoNormalizada, [
                'CAJA CHICA',
                'FONDO POR RENDIR',
            ], true);

            // =========================
            // ESTADO FINANCIERO INICIAL + FECHA VENCIMIENTO
            // =========================
            $estadoFinancieroInicial = null;
            $fechaVencimiento = null;

            if ($cobranza && $cobranza->creditos !== null) {
                $fechaEmision = !empty($r['fecha_emision'])
                    ? Carbon::parse($r['fecha_emision'])
                    : null;

                if ($fechaEmision) {
                    $fechaVencimiento = $fechaEmision
                        ->copy()
                        ->addDays((int) $cobranza->creditos);

                    $estadoFinancieroInicial = $fechaVencimiento->isPast()
                        ? 'Vencido'
                        : 'Al día';
                }
            }

            // =========================
            // REGLA: DOCUMENTO ANULADO/NULO => SALDO PENDIENTE 0
            // =========================
            $estadoDocumento = mb_strtoupper(trim((string) ($r['estado'] ?? '')));
            $documentoCerradoPorSii = in_array($estadoDocumento, ['NULA', 'ANULADA'], true);

            // =========================
            // REGLA: CAJA CHICA / FONDO POR RENDIR => CIERRE AUTOMÁTICO
            // =========================
            $debeCerrarAutomaticamente = !$documentoCerradoPorSii && $esPagoAutomatico;

            $honorario = HonorarioMensualRec::where([
                'empresa_id'        => $empresaId,
                'tipo_boleta'       => $tipoBoleta,
                'rut_contribuyente' => $rutContribuyenteDocumento,
                'anio'              => $anioDocumento,
                'mes'               => $mesDocumento,
                'rut_emisor'        => $rutEmisor,
                'folio'             => $r['folio'],
            ])->first();

            if ($honorario) {
                $honorario->update([
                    'tipo_boleta'          => $tipoBoleta,
                    'rut_contribuyente'    => $rutContribuyenteDocumento,
                    'razon_social'         => $razonSocialDocumento,
                    'fecha_emision'        => $r['fecha_emision'] ?? null,
                    'estado'               => $r['estado'],
                    'fecha_anulacion'      => $r['fecha_anulacion'] ?? null,
                    'razon_social_emisor'  => $r['razon_social_emisor'],
                    'sociedad_profesional' => $r['sociedad_profesional'] ?? 0,

                    'monto_bruto'    => $r['monto_bruto'],
                    'monto_retenido' => $r['monto_retenido'],
                    'monto_pagado'   => $r['monto_pagado'],

                    'cobranza_compra_id' => $cobranza?->id,
                ]);

                if ($documentoCerradoPorSii && (int) $honorario->saldo_pendiente !== 0) {
                    $honorario->update([
                        'saldo_pendiente' => 0,
                    ]);
                }
            } else {
                $honorario = HonorarioMensualRec::create([
                    'empresa_id'        => $empresaId,
                    'tipo_boleta'       => $tipoBoleta,
                    'rut_contribuyente' => $rutContribuyenteDocumento,
                    'anio'              => $anioDocumento,
                    'mes'               => $mesDocumento,
                    'rut_emisor'        => $rutEmisor,
                    'folio'             => $r['folio'],

                    'razon_social'         => $razonSocialDocumento,
                    'fecha_emision'        => $r['fecha_emision'] ?? null,
                    'estado'               => $r['estado'],
                    'fecha_anulacion'      => $r['fecha_anulacion'] ?? null,
                    'razon_social_emisor'  => $r['razon_social_emisor'],
                    'sociedad_profesional' => $r['sociedad_profesional'] ?? 0,

                    'monto_bruto'    => $r['monto_bruto'],
                    'monto_retenido' => $r['monto_retenido'],
                    'monto_pagado'   => $r['monto_pagado'],

                    'saldo_pendiente' => $documentoCerradoPorSii
                        ? 0
                        : (int) ($r['monto_pagado'] ?? 0),

                    'estado_financiero_inicial' => $estadoFinancieroInicial,
                    'estado_financiero'         => null,
                    'fecha_estado_financiero'   => null,
                    'fecha_vencimiento'         => $fechaVencimiento,

                    'cobranza_compra_id' => $cobranza?->id,
                ]);
            }

            if ($debeCerrarAutomaticamente && $honorario) {
                $yaEstabaCerradoAutomaticamente =
                    (int) $honorario->saldo_pendiente === 0 &&
                    $honorario->estado_financiero === 'Pago' &&
                    $honorario->pagos()->exists();

                if (!$yaEstabaCerradoAutomaticamente) {
                    $estadoAnterior = $honorario->estado_financiero;
                    $saldoAnterior  = (int) $honorario->saldo_pendiente;

                    $fechaPagoAutomatica = !empty($r['fecha_emision'])
                        ? Carbon::parse($r['fecha_emision'])->format('Y-m-d')
                        : now()->format('Y-m-d');

                    $honorario->update([
                        'estado_financiero'       => 'Pago',
                        'fecha_estado_financiero' => now(),
                        'saldo_pendiente'         => 0,
                    ]);

                    if (!$honorario->pagos()->exists()) {
                        $honorario->pagos()->create([
                            'fecha_pago' => $fechaPagoAutomatica,
                            'user_id'    => Auth::id(),
                        ]);
                    }

                    $honorario->movimientos()->create([
                        'usuario_id'      => Auth::id(),
                        'estado_anterior' => $estadoAnterior,
                        'nuevo_estado'    => 'Pago',
                        'fecha_cambio'    => now(),
                        'tipo_movimiento' => 'Pago automático por forma de pago',
                        'descripcion'     => 'Honorario cerrado automáticamente al importar por forma de pago ' . $formaPagoNormalizada . '.',
                        'datos_anteriores'=> [
                            'saldo'      => $saldoAnterior,
                            'forma_pago' => $cobranza->forma_pago ?? null,
                        ],
                        'datos_nuevos'    => [
                            'fecha_pago' => $fechaPagoAutomatica,
                            'saldo'      => 0,
                        ],
                    ]);
                }
            }
        }

        // =========================
        // GUARDAR TOTALES
        // =========================
        if (!empty($data['totales'])) {
            $rutContribuyenteTotal = $meta['rut_contribuyente']
                ?? collect($registros)->pluck('rut_contribuyente')->filter()->first();

            $razonSocialTotal = $meta['razon_social']
                ?? collect($registros)->pluck('razon_social')->filter()->first();

            if ($rutContribuyenteTotal && $anioDocumento && $mesDocumento) {
                HonorarioMensualRecTotal::updateOrCreate(
                    [
                        'rut_contribuyente' => $rutContribuyenteTotal,
                        'anio'              => $anioDocumento,
                        'mes'               => $mesDocumento,
                    ],
                    [
                        'razon_social'   => $razonSocialTotal,
                        'monto_bruto'    => $data['totales']['bruto'],
                        'monto_retenido' => $data['totales']['retenido'],
                        'monto_pagado'   => $data['totales']['pagado'],
                    ]
                );
            }
        }

        return true;
    }
}