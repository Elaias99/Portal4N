<?php

namespace App\Services\Courier;

use App\Models\CourierAcuerdo;
use App\Models\CourierCierre;
use App\Models\CourierPagoProceso;
use App\Models\CourierPeriodo;
use App\Models\CourierProveedor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * El cierre del mes, como en LogisticaCL. Junta todo lo que se paga
 * (bultos, Acuerdos y los demás procesos), le asigna una OC a cada
 * proveedor, calcula el impuesto por documento y lo guarda en
 * courier_pagos_cerrados, que ya no se puede modificar.
 *
 * Una OC agrupa zona + RUT del proveedor + empresa mandante (4N o PMCB),
 * numeradas {período}0001, 0002… en orden: RM antes que Regiones y,
 * dentro de cada zona, por razón social. Una OC no mezcla factura y
 * boleta.
 *
 * Lo que no tiene a quién o cómo pagarse queda fuera del cierre con su
 * motivo, y puede pagarse en un período siguiente.
 */
class CourierCierreService
{
    public const FUERA = [
        'ya_pagado' => 'El bulto ya se pagó en otro período',
        'sin_proveedor' => 'No tiene proveedor identificado',
        'sin_rut' => 'El proveedor no tiene RUT',
        'sin_documento' => 'El documento del proveedor no tiene regla de impuesto',
        'sin_zona' => 'No tiene zona',
    ];

    private const ORDEN_ZONA = ['RM' => 0, 'Regiones' => 1];

    /*
     * Arma el cierre sin guardar nada: qué se paga, en qué OC, con qué
     * impuesto, y qué queda fuera.
     */
    public function armar(CourierPeriodo $periodo): array
    {
        $proveedores = CourierProveedor::query()
            ->get(['id', 'rut', 'razon_social', 'tipo_documento'])
            ->keyBy('id');

        $yaPagados = $this->seguimientosYaPagados();

        $pagos = [];
        $fuera = [];

        $agregar = function (array $pago) use ($proveedores, $yaPagados, &$pagos, &$fuera) {
            $proveedor = $pago['courier_proveedor_id'] ? $proveedores->get($pago['courier_proveedor_id']) : null;

            $motivo = match (true) {
                $pago['seguimiento'] !== null && $yaPagados->has($pago['seguimiento']) => 'ya_pagado',
                $proveedor === null => 'sin_proveedor',
                trim((string) $proveedor->rut) === '' => 'sin_rut',
                ! CourierImpuestos::tieneRegla($proveedor->tipo_documento) => 'sin_documento',
                ! isset(self::ORDEN_ZONA[$pago['zona']]) => 'sin_zona',
                default => null,
            };

            if ($motivo !== null) {
                $fuera[$motivo]['registros'] = ($fuera[$motivo]['registros'] ?? 0) + 1;
                $fuera[$motivo]['monto'] = ($fuera[$motivo]['monto'] ?? 0) + $pago['valor'];

                return;
            }

            $pagos[] = $pago + [
                'rut_proveedor' => trim($proveedor->rut),
                'razon_social' => trim((string) $proveedor->razon_social),
                'tipo_documento' => $proveedor->tipo_documento,
            ];
        };

        DB::table('courier_bultos')
            ->where('courier_periodo_id', $periodo->id)
            ->where('estado_pago', 'PAGAR')
            ->select(['id', 'seguimiento', 'tipo_pago', 'zona', 'courier_proveedor_id', 'valor', 'peso_pago', 'servicio'])
            ->orderBy('id')
            ->chunk(5000, function ($bultos) use ($agregar) {
                foreach ($bultos as $b) {
                    $agregar([
                        'origen' => 'bulto',
                        'origen_id' => $b->id,
                        'seguimiento' => strtoupper(trim($b->seguimiento)),
                        'tipo_pago' => $b->tipo_pago ?: CourierCalculoService::TIPO_VARIABLES,
                        'zona' => $b->zona,
                        'courier_proveedor_id' => $b->courier_proveedor_id,
                        'empresa_mandante' => '4N',
                        'concepto' => $b->servicio,
                        'cantidad' => $b->peso_pago,
                        'valor' => (int) $b->valor,
                    ]);
                }
            });

        foreach (CourierAcuerdo::delPeriodo($periodo->id)->where('total', '>', 0)->orderBy('id')->get() as $a) {
            $agregar([
                'origen' => 'acuerdo',
                'origen_id' => $a->id,
                'seguimiento' => null,
                'tipo_pago' => CourierAcuerdo::TIPO_PAGO,
                'zona' => $a->zona,
                'courier_proveedor_id' => $a->courier_proveedor_id,
                'empresa_mandante' => $a->empresa_mandante ?: '4N',
                'concepto' => $a->servicio,
                'cantidad' => $a->cantidad,
                'valor' => $a->total,
            ]);
        }

        foreach (CourierPagoProceso::delPeriodo($periodo->id)->where('total', '>', 0)->orderBy('id')->get() as $p) {
            $agregar([
                'origen' => 'proceso',
                'origen_id' => $p->id,
                'seguimiento' => null,
                'tipo_pago' => $p->proceso,
                'zona' => $p->zona,
                'courier_proveedor_id' => $p->courier_proveedor_id,
                'empresa_mandante' => ($p->datos['empresa_mandante'] ?? null) ?: (($p->datos['empresa'] ?? null) ?: '4N'),
                'concepto' => $p->concepto,
                'cantidad' => $p->cantidad,
                'valor' => $p->total,
            ]);
        }

        $ordenes = $this->ordenesDeCompra($periodo, $pagos);

        $bases = [];
        $asignados = [];
        $neto = 0;
        $iva = 0;
        $retencion = 0;
        $total = 0;

        foreach ($pagos as &$pago) {
            $pago['oc'] = $ordenes[$this->claveOc($pago)];

            $impuesto = CourierImpuestos::repartirEnOc($pago['tipo_documento'], $pago['valor'], $pago['oc'], $bases, $asignados);

            $pago['impuesto'] = $impuesto['impuesto'];
            $pago['porcentaje_impuesto'] = $impuesto['porcentaje'];
            $pago['valor_impuesto'] = $impuesto['valor_impuesto'];
            $pago['valor_final_total'] = $impuesto['total'];

            $neto += $pago['valor'];
            $iva += $impuesto['impuesto'] === CourierImpuestos::IVA ? $impuesto['valor_impuesto'] : 0;
            $retencion += $impuesto['impuesto'] === CourierImpuestos::RETENCION ? $impuesto['valor_impuesto'] : 0;
            $total += $impuesto['total'];
        }
        unset($pago);

        return [
            'pagos' => $pagos,
            'fuera' => $fuera,
            'ordenes_compra' => count($ordenes),
            'neto' => $neto,
            'iva' => $iva,
            'retencion' => $retencion,
            'total' => $total,
        ];
    }

    /* Guarda el cierre. Después de esto el período no admite cambios. */
    public function cerrar(CourierPeriodo $periodo, ?int $userId = null): array
    {
        if ($periodo->estaCerrado() || CourierCierre::where('courier_periodo_id', $periodo->id)->exists()) {
            throw new \RuntimeException("El período {$periodo->nombre} ya está cerrado.");
        }

        $cierre = $this->armar($periodo);

        if ($cierre['pagos'] === []) {
            throw new \RuntimeException("El período {$periodo->nombre} no tiene pagos para cerrar.");
        }

        DB::transaction(function () use ($periodo, $userId, $cierre) {
            $ahora = now()->format('Y-m-d H:i:s');

            foreach (array_chunk($cierre['pagos'], 500) as $lote) {
                DB::table('courier_pagos_cerrados')->insert(array_map(fn ($pago) => [
                    'courier_periodo_id' => $periodo->id,
                    'origen' => $pago['origen'],
                    'origen_id' => $pago['origen_id'],
                    'seguimiento' => $pago['seguimiento'],
                    'tipo_pago' => $pago['tipo_pago'],
                    'zona' => $pago['zona'],
                    'courier_proveedor_id' => $pago['courier_proveedor_id'],
                    'rut_proveedor' => $pago['rut_proveedor'],
                    'razon_social' => Str::limit($pago['razon_social'], 255, ''),
                    'tipo_documento' => $pago['tipo_documento'],
                    'empresa_mandante' => Str::limit($pago['empresa_mandante'], 20, ''),
                    'concepto' => $pago['concepto'] === null ? null : Str::limit($pago['concepto'], 255, ''),
                    'cantidad' => $pago['cantidad'],
                    'valor' => $pago['valor'],
                    'oc' => $pago['oc'],
                    'impuesto' => $pago['impuesto'],
                    'porcentaje_impuesto' => $pago['porcentaje_impuesto'],
                    'valor_impuesto' => $pago['valor_impuesto'],
                    'valor_final_total' => $pago['valor_final_total'],
                    'closed_at' => $ahora,
                ], $lote));
            }

            CourierCierre::create([
                'courier_periodo_id' => $periodo->id,
                'registros' => count($cierre['pagos']),
                'ordenes_compra' => $cierre['ordenes_compra'],
                'neto' => $cierre['neto'],
                'iva' => $cierre['iva'],
                'retencion' => $cierre['retencion'],
                'total' => $cierre['total'],
                'user_id' => $userId,
                'closed_at' => $ahora,
            ]);

            $periodo->update(['estado' => 'cerrado']);
        });

        return $cierre;
    }

    /* Bultos que ya están en un cierre, de cualquier período. */
    protected function seguimientosYaPagados(): Collection
    {
        return DB::table('courier_pagos_cerrados')
            ->whereNotNull('seguimiento')
            ->pluck('seguimiento')
            ->flip();
    }

    /** @return array<string, string> clave de OC → número */
    private function ordenesDeCompra(CourierPeriodo $periodo, array $pagos): array
    {
        $grupos = [];

        foreach ($pagos as $pago) {
            $clave = $this->claveOc($pago);
            $nombre = $this->normalizar($pago['razon_social']);
            $familia = CourierImpuestos::familia($pago['tipo_documento']);

            if (isset($grupos[$clave]) && $grupos[$clave]['familia'] !== $familia) {
                throw new \RuntimeException(
                    "La OC del proveedor {$pago['rut_proveedor']} reúne factura y boleta. Hay que separar esos pagos antes del cierre."
                );
            }

            if (! isset($grupos[$clave]) || strcmp($nombre, $grupos[$clave]['nombre']) < 0) {
                $grupos[$clave] = [
                    'zona' => self::ORDEN_ZONA[$pago['zona']],
                    'nombre' => $nombre,
                    'empresa' => $this->normalizar($pago['empresa_mandante']),
                    'familia' => $familia,
                ];
            }
        }

        uksort($grupos, fn ($a, $b) => [$grupos[$a]['zona'], $grupos[$a]['nombre'], $grupos[$a]['empresa'], $a]
            <=> [$grupos[$b]['zona'], $grupos[$b]['nombre'], $grupos[$b]['empresa'], $b]);

        $ordenes = [];
        $numero = 0;

        foreach (array_keys($grupos) as $clave) {
            $numero++;

            if ($numero > 9999) {
                throw new \RuntimeException("El período {$periodo->codigo} superó las 9.999 órdenes de compra.");
            }

            $ordenes[$clave] = sprintf('%s%04d', $periodo->codigo, $numero);
        }

        return $ordenes;
    }

    private function claveOc(array $pago): string
    {
        return $pago['zona'] . '|'
            . CourierProveedoresPorRut::normalizar($pago['rut_proveedor']) . '|'
            . $this->normalizar($pago['empresa_mandante']);
    }

    private function normalizar(string $valor): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(Str::ascii(trim($valor)))) ?? '';
    }
}
