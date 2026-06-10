@extends('layouts.app')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pre-factura de servicios</h1>

        <a href="{{ route('suscripciones.liquidacion-detalles.index', [
            'proveedor' => $cobranzaCompra?->razon_social,
            'anio' => $detalle->anio,
            'mes' => $detalle->mes,
        ]) }}" class="btn btn-secondary">
            Volver
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>{{ mb_strtoupper($meses[$detalle->mes] ?? $detalle->mes) }} {{ $detalle->anio }}</h4>

                    <p class="mb-1">
                        <strong>Proveedor:</strong>
                        {{ $cobranzaCompra?->razon_social ?? '—' }}
                    </p>

                    <p class="mb-1">
                        <strong>RUT:</strong>
                        {{ $cobranzaCompra?->rut_cliente ?? '—' }}
                    </p>

                    <p class="mb-1">
                        <strong>Tipo documento:</strong>
                        {{ $proveedor?->tipo ?? '—' }}
                    </p>

                    <p class="mb-0">
                        <strong>Servicio:</strong>
                        {{ $cobranzaCompra?->servicio ?? 'SUSCRIPCIONES' }}
                    </p>
                </div>

                <div>
                    <a href="{{ route('suscripciones.liquidacion-detalles.pdf', $detalle->id) }}"
                       class="btn btn-danger"
                       target="_blank">
                        Generar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- OPV sin puntos: acceso desde observaciones --}}
    @if(($opvPendientes ?? collect())->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <strong>Observaciones OPV</strong>
            </div>

            <div class="card-body">
                @foreach($opvPendientes as $opvPendiente)
                    <a href="{{ route('suscripciones.liquidacion-detalles.opv-puntos', $opvPendiente->id) }}"
                       class="text-decoration-none text-reset">
                        <div class="border rounded p-3 mb-2">
                            <div class="fw-semibold mb-1">
                                Ruta OPV sin locales asignados
                            </div>

                            <div class="small">
                                Este proveedor tiene una ruta OPV en
                                <strong>{{ $opvPendiente->punto_1 ?? '—' }}</strong>,
                                pero no se generó porque no tiene locales OPV asignados.
                            </div>

                            <div class="small text-muted mt-1">
                                Transportista:
                                {{ $opvPendiente->transportista?->nombre_transportista ?? '—' }}
                                |
                                Código:
                                {{ $opvPendiente->codigo ?? '—' }}
                                |
                                Costo base:
                                ${{ number_format($opvPendiente->costo ?? 0, 0, ',', '.') }}
                            </div>

                            <div class="small text-muted mt-2">
                                Haz clic para revisar los puntos OPV registrados para esta asignación.
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <strong>Detalle de servicios</strong>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Detalle</th>
                            <th class="text-end">Valor</th>
                            <th class="text-center">Base cálculo</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Neto/Bruto</th>
                            <th class="text-end">Impuesto</th>
                            <th class="text-end">Total Impuesto</th>
                            <th class="text-end">{{ $proveedor?->final ?? 'Neto' }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($detallesProveedor as $item)
                            @php
                                $calculo = $calculosDetalle[$item->id] ?? null;

                                $codigo = mb_strtoupper(trim((string) $item->codigo));
                                $servicio = mb_strtoupper(trim((string) ($item->asignacion?->servicio ?? '')));
                                $origenGasto = mb_strtoupper(trim((string) ($item->asignacion?->origen_gasto ?? '')));

                                $esValorFijo = str_ends_with($codigo, '.COM');

                                $esOPV = $codigo === 'OPV'
                                    || str_ends_with($codigo, '.OPV')
                                    || $servicio === 'OPV'
                                    || $origenGasto === 'OPV';

                                $puntosOPV = $item->asignacion?->opvPuntos?->count() ?? 0;

                                $nombresPuntosOPV = $item->asignacion?->opvPuntos
                                    ?->pluck('nombre_local_corto')
                                    ->filter()
                                    ->implode(', ');
                            @endphp

                            <tr>
                                <td>
                                    {{ $item->asignacion?->punto_1 ?? '—' }}
                                    /
                                    {{ $item->asignacion?->servicio ?? '—' }}
                                    ({{ $item->codigo }})

                                    @if($esOPV)
                                        <small class="text-muted d-block mt-1">
                                            OPV: {{ $puntosOPV }} punto{{ $puntosOPV === 1 ? '' : 's' }} asociado{{ $puntosOPV === 1 ? '' : 's' }}.

                                            @if($nombresPuntosOPV)
                                                {{ $nombresPuntosOPV }}.
                                            @endif
                                        </small>

                                        @if($item->asignacion)
                                            <a href="{{ route('suscripciones.liquidacion-detalles.opv-puntos', $item->asignacion->id) }}"
                                               class="btn btn-outline-secondary btn-sm mt-2">
                                                Ver puntos OPV
                                            </a>
                                        @endif
                                    @elseif($esValorFijo)
                                        <small class="text-muted d-block mt-1">
                                            Valor fijo mensual. No se multiplica por fines de semana.
                                        </small>
                                    @endif
                                </td>

                                <td class="text-end">
                                    ${{ number_format($item->costo, 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    @if($esOPV)
                                        {{ $item->q_calendario }} fines de semana
                                        <small class="text-muted d-block">
                                            x {{ $puntosOPV }} punto{{ $puntosOPV === 1 ? '' : 's' }} OPV
                                        </small>
                                    @elseif($esValorFijo)
                                        Fijo
                                    @else
                                        {{ $item->q_calendario }} fines de semana

                                        @if($item->q_inasistencia > 0)
                                            <small class="text-muted d-block">
                                                - {{ $item->q_inasistencia }} inasistencia{{ $item->q_inasistencia === 1 ? '' : 's' }}
                                            </small>
                                        @endif
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($esValorFijo)
                                        1
                                        <small class="text-muted d-block">
                                            fijo
                                        </small>
                                    @else
                                        {{ $item->cantidad }}

                                        @if($esOPV)
                                            <small class="text-muted d-block">
                                                {{ $item->q_calendario }} x {{ $puntosOPV }}
                                            </small>
                                        @endif
                                    @endif
                                </td>

                                <td class="text-end fw-bold">
                                    ${{ number_format($item->total, 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    {{ number_format($calculo['impuesto'] ?? 0, 2, ',', '.') }}%
                                </td>

                                <td class="text-end">
                                    ${{ number_format($calculo['total_impuesto'] ?? 0, 1, ',', '.') }}
                                </td>

                                <td class="text-end fw-bold">
                                    ${{ number_format($calculo['liquido'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">
                                Total {{ mb_strtolower($proveedor?->detalle_documento ?? 'bruto') }}
                            </th>

                            <th class="text-end">
                                ${{ number_format($totalBruto, 0, ',', '.') }}
                            </th>

                            <th></th>

                            <th class="text-end">
                                ${{ number_format($totalImpuesto, 0, ',', '.') }}
                            </th>

                            <th class="text-end">
                                ${{ number_format($totalLiquido, 0, ',', '.') }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Estado de pre-facturas --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Estado de pre-facturas {{ $detalle->anio }}</strong>
        </div>

        <div class="card-body">
            <div class="row g-2">
                @foreach($estadoPrefacturas ?? [] as $estado)
                    <div class="col-6 col-md-4 col-lg-2">
                        @if($estado['generada'])
                            <a href="{{ route('suscripciones.liquidacion-detalles.show', $estado['detalle_id']) }}"
                               class="text-decoration-none text-reset">
                                <div class="border rounded p-2 h-100 {{ $estado['es_actual'] ? 'border-dark' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong>
                                            {{ mb_strtoupper(mb_substr($estado['mes_nombre'], 0, 3)) }}
                                        </strong>

                                        <span>✓</span>
                                    </div>

                                    <div class="small">
                                        Generada
                                    </div>

                                    <div class="small text-muted">
                                        {{ $estado['cantidad_lineas'] }} línea{{ $estado['cantidad_lineas'] === 1 ? '' : 's' }}
                                    </div>

                                    <div class="small fw-semibold mt-1">
                                        ${{ number_format($estado['total_final'], 0, ',', '.') }}
                                    </div>
                                </div>
                            </a>
                        @else
                            <div class="border rounded p-2 h-100 text-muted">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong>
                                        {{ mb_strtoupper(mb_substr($estado['mes_nombre'], 0, 3)) }}
                                    </strong>

                                    <span>—</span>
                                </div>

                                <div class="small">
                                    Pendiente
                                </div>

                                <div class="small">
                                    Sin pre-factura
                                </div>

                                <div class="small mt-1">
                                    $0
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="small text-muted mt-3">
                El mes con borde marcado corresponde a la pre-factura que estás revisando actualmente.
            </div>
        </div>
    </div>

</div>
@endsection