<?php

namespace App\Services\Ventas;

use App\Models\Abono;
use App\Models\Cruce;
use App\Models\Factory;
use App\Models\MovimientoDocumento;
use App\Models\Pago;
use App\Models\ProntoPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HistorialMovimientosCxCService
{
    public function aplicarFiltroFechaMovimiento(
        Builder $query,
        ?string $fechaInicio,
        ?string $fechaFin
    ): Builder {
        if (!$fechaInicio && !$fechaFin) {
            return $query;
        }

        return $query->where(function ($sub) use ($fechaInicio, $fechaFin) {
            $this->aplicarFiltroFechaConOrigen($sub, $fechaInicio, $fechaFin);
            $this->aplicarFiltroFechaSinOrigen($sub, $fechaInicio, $fechaFin);
        });
    }

    public function enriquecerPaginador(LengthAwarePaginator $movimientos): LengthAwarePaginator
    {
        $coleccion = $this->enriquecerColeccion($movimientos->getCollection());

        $movimientos->setCollection($coleccion);

        return $movimientos;
    }

    public function totalizar(Collection $movimientos): int
    {
        return (int) $movimientos->sum(function ($movimiento) {
            return (int) ($movimiento->monto_movimiento_historial ?? 0);
        });
    }

    private function definiciones(): array
    {
        return [
            'factoring' => [
                'aliases' => ['factoring', 'factory'],
                'keywords' => ['Factoring', 'Factory'],
                'excluir_keywords' => [],
                'model' => Factory::class,
                'fecha_campos' => ['fecha_factory'],
                'monto_campos' => ['saldo_liquido', 'monto'],
                'afecta_saldo' => true,
                'cierra_saldo' => false,
            ],

            'pronto_pago' => [
                'aliases' => ['pronto pago'],
                'keywords' => ['Pronto pago'],
                'excluir_keywords' => [],
                'model' => ProntoPago::class,
                'fecha_campos' => ['fecha_pronto_pago'],
                'monto_campos' => ['monto', 'saldo_anterior'],
                'afecta_saldo' => true,
                'cierra_saldo' => true,
            ],

            'abono' => [
                'aliases' => ['abono'],
                'keywords' => ['Abono'],
                'excluir_keywords' => [],
                'model' => Abono::class,
                'fecha_campos' => ['fecha_abono'],
                'monto_campos' => ['monto'],
                'afecta_saldo' => true,
                'cierra_saldo' => false,
            ],

            'cruce' => [
                'aliases' => ['cruce'],
                'keywords' => ['Cruce'],
                'excluir_keywords' => [],
                'model' => Cruce::class,
                'fecha_campos' => ['fecha_cruce'],
                'monto_campos' => ['monto'],
                'afecta_saldo' => true,
                'cierra_saldo' => false,
            ],

            'pago' => [
                'aliases' => ['pago'],
                'keywords' => ['Pago'],
                'excluir_keywords' => ['Pronto pago'],
                'model' => Pago::class,
                'fecha_campos' => ['fecha_pago'],
                'monto_campos' => ['monto', 'saldo_anterior'],
                'afecta_saldo' => true,
                'cierra_saldo' => true,
            ],
        ];
    }

    private function aplicarFiltroFechaConOrigen($query, ?string $fechaInicio, ?string $fechaFin): void
    {
        $modelos = collect($this->definiciones())
            ->pluck('model')
            ->unique()
            ->values()
            ->all();

        $query->orWhereHasMorph(
            'origen',
            $modelos,
            function ($origenQuery, $type) use ($fechaInicio, $fechaFin) {
                $definicion = $this->definicionPorModelo($type);

                if (!$definicion) {
                    return;
                }

                $origenQuery->where(function ($fechaQuery) use ($definicion, $fechaInicio, $fechaFin) {
                    foreach ($definicion['fecha_campos'] as $campoFecha) {
                        $fechaQuery->orWhere(function ($campoQuery) use ($campoFecha, $fechaInicio, $fechaFin) {
                            if ($fechaInicio) {
                                $campoQuery->whereDate($campoFecha, '>=', $fechaInicio);
                            }

                            if ($fechaFin) {
                                $campoQuery->whereDate($campoFecha, '<=', $fechaFin);
                            }
                        });
                    }
                });
            }
        );
    }

    private function aplicarFiltroFechaSinOrigen($query, ?string $fechaInicio, ?string $fechaFin): void
    {
        $query->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereNull('origen_id')
                ->where(function ($sub) use ($fechaInicio, $fechaFin) {
                    foreach ($this->definiciones() as $definicion) {
                        $sub->orWhere(function ($movimientoQuery) use ($definicion, $fechaInicio, $fechaFin) {
                            $this->aplicarFiltroTipoMovimiento($movimientoQuery, $definicion);
                            $this->aplicarFiltroFechaJson($movimientoQuery, $definicion, $fechaInicio, $fechaFin);
                        });
                    }
                });
        });
    }

    private function aplicarFiltroTipoMovimiento($query, array $definicion): void
    {
        $query->where(function ($tipoQuery) use ($definicion) {
            foreach ($definicion['keywords'] as $keyword) {
                $tipoQuery->orWhere('tipo_movimiento', 'like', "%{$keyword}%");
            }
        });

        foreach ($definicion['excluir_keywords'] as $keywordExcluir) {
            $query->where('tipo_movimiento', 'not like', "%{$keywordExcluir}%");
        }
    }

    private function aplicarFiltroFechaJson(
        $query,
        array $definicion,
        ?string $fechaInicio,
        ?string $fechaFin
    ): void {
        $query->where(function ($fechaQuery) use ($definicion, $fechaInicio, $fechaFin) {
            foreach ($definicion['fecha_campos'] as $campoFecha) {
                $fechaQuery->orWhere(function ($campoQuery) use ($campoFecha, $fechaInicio, $fechaFin) {
                    $expresionFecha = $this->jsonDateExpression($campoFecha);

                    if ($fechaInicio) {
                        $campoQuery->whereDate($expresionFecha, '>=', $fechaInicio);
                    }

                    if ($fechaFin) {
                        $campoQuery->whereDate($expresionFecha, '<=', $fechaFin);
                    }
                });
            }
        });
    }

    private function jsonDateExpression(string $campo): \Illuminate\Database\Query\Expression
    {
        return DB::raw(
            "COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(datos_nuevos, '$.{$campo}')),
                JSON_UNQUOTE(JSON_EXTRACT(datos_anteriores, '$.{$campo}'))
            )"
        );
    }

    public function enriquecerColeccion(Collection $movimientos): Collection
    {
        if ($movimientos->isEmpty()) {
            return $movimientos;
        }

        $documentoIds = $movimientos
            ->pluck('documento_financiero_id')
            ->filter()
            ->unique()
            ->values();

        if ($documentoIds->isEmpty()) {
            return $movimientos;
        }

        $historialPorDocumento = MovimientoDocumento::with([
                'documento.referenciados',
                'origen',
            ])
            ->whereIn('documento_financiero_id', $documentoIds)
            ->orderBy('documento_financiero_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('documento_financiero_id');

        $calculados = $this->calcularMontosPorMovimiento($historialPorDocumento);

        return $movimientos->map(function ($movimiento) use ($calculados) {
            $datos = $calculados[$movimiento->id] ?? [
                'monto' => 0,
                'fecha' => null,
            ];

            $movimiento->monto_movimiento_historial = (int) $datos['monto'];
            $movimiento->fecha_estado_historial = $datos['fecha'];

            return $movimiento;
        });
    }

    private function calcularMontosPorMovimiento(Collection $historialPorDocumento): array
    {
        $calculados = [];

        foreach ($historialPorDocumento as $historialDocumento) {
            $documento = $historialDocumento->first()?->documento;
            $saldoVisual = $this->calcularSaldoBaseDocumento($documento);
            $ultimosCierres = [];

            foreach ($historialDocumento as $movimiento) {
                $definicion = $this->definicionPorMovimiento($movimiento);

                if (!$definicion) {
                    $calculados[$movimiento->id] = [
                        'monto' => 0,
                        'fecha' => null,
                    ];

                    continue;
                }

                $clave = $this->claveDefinicion($definicion);
                $fecha = $this->resolverFechaMovimiento($movimiento, $definicion);
                $monto = $this->resolverMontoDirecto($movimiento, $definicion);
                $esEliminacion = $this->esEliminacion($movimiento);

                $montoFirmado = 0;

                if ($definicion['cierra_saldo']) {
                    if ($esEliminacion) {
                        if ($monto <= 0) {
                            $monto = $ultimosCierres[$clave] ?? 0;
                        }

                        $montoFirmado = $monto * -1;
                        $saldoVisual += $monto;
                    } else {
                        if ($monto <= 0) {
                            $monto = $saldoVisual;
                        }

                        $montoFirmado = $monto;
                        $saldoVisual = 0;
                        $ultimosCierres[$clave] = $monto;
                    }
                } elseif ($definicion['afecta_saldo']) {
                    if ($esEliminacion) {
                        $montoFirmado = $monto * -1;
                        $saldoVisual += $monto;
                    } else {
                        $montoFirmado = $monto;
                        $saldoVisual = max($saldoVisual - $monto, 0);
                    }
                }

                $calculados[$movimiento->id] = [
                    'monto' => $montoFirmado,
                    'fecha' => $fecha,
                ];
            }
        }

        return $calculados;
    }

    private function calcularSaldoBaseDocumento($documento): int
    {
        if (!$documento) {
            return 0;
        }

        $saldo = (int) ($documento->monto_total ?? 0);

        $referenciados = $documento->relationLoaded('referenciados')
            ? $documento->referenciados
            : collect();

        $saldo -= (int) $referenciados
            ->where('tipo_documento_id', 61)
            ->sum('monto_total');

        $saldo += (int) $referenciados
            ->where('tipo_documento_id', 56)
            ->sum('monto_total');

        return max($saldo, 0);
    }

    private function resolverFechaMovimiento($movimiento, array $definicion): ?string
    {
        return $this->obtenerValorMovimiento(
            $movimiento,
            $definicion['fecha_campos']
        );
    }

    private function resolverMontoDirecto($movimiento, array $definicion): int
    {
        $valor = $this->obtenerValorMovimiento(
            $movimiento,
            $definicion['monto_campos']
        );

        return $this->normalizarMonto($valor);
    }

    private function obtenerValorMovimiento($movimiento, array $campos)
    {
        $datosNuevos = is_array($movimiento->datos_nuevos)
            ? $movimiento->datos_nuevos
            : [];

        $datosAnteriores = is_array($movimiento->datos_anteriores)
            ? $movimiento->datos_anteriores
            : [];

        foreach ($campos as $campo) {
            if (
                array_key_exists($campo, $datosNuevos) &&
                $datosNuevos[$campo] !== null &&
                $datosNuevos[$campo] !== ''
            ) {
                return $datosNuevos[$campo];
            }

            if (
                array_key_exists($campo, $datosAnteriores) &&
                $datosAnteriores[$campo] !== null &&
                $datosAnteriores[$campo] !== ''
            ) {
                return $datosAnteriores[$campo];
            }
        }

        return null;
    }

    private function normalizarMonto($valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_numeric($valor)) {
            return (int) $valor;
        }

        return (int) preg_replace('/[^\d-]/', '', (string) $valor);
    }

    private function definicionPorModelo(string $modelo): ?array
    {
        foreach ($this->definiciones() as $definicion) {
            if ($definicion['model'] === $modelo) {
                return $definicion;
            }
        }

        return null;
    }

    private function definicionPorMovimiento($movimiento): ?array
    {
        $tipo = $this->tipoNormalizado($movimiento);

        foreach ($this->definiciones() as $definicion) {
            foreach ($definicion['aliases'] as $alias) {
                if (str_contains($tipo, $alias)) {
                    return $definicion;
                }
            }
        }

        return null;
    }

    private function claveDefinicion(array $definicion): string
    {
        return $definicion['aliases'][0] ?? 'movimiento';
    }

    private function tipoNormalizado($movimiento): string
    {
        return Str::ascii(
            mb_strtolower((string) ($movimiento->tipo_movimiento ?? ''))
        );
    }

    private function esEliminacion($movimiento): bool
    {
        return str_contains($this->tipoNormalizado($movimiento), 'eliminacion');
    }
}