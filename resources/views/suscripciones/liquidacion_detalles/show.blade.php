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
    </div>

    <div class="mb-3">
        {{-- Aquí después conectamos la ruta PDF --}}
        <a href="{{ route('suscripciones.liquidacion-detalles.pdf', $detalle->id) }}"
        class="btn btn-danger"
        target="_blank">
            Generar PDF
        </a>
    </div>

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
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Impuesto</th>
                            <th class="text-end">Total Impuesto</th>
                            <th class="text-end">Neto</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($detallesProveedor as $item)
                            @php
                                $calculo = $calculosDetalle[$item->id] ?? null;
                                $esValorFijo = str_ends_with(mb_strtoupper(trim($item->codigo)), '.COM');
                            @endphp

                            <tr>
                                <td>
                                    {{ $item->asignacion?->punto_1 ?? '—' }}
                                    /
                                    {{ $item->asignacion?->servicio ?? '—' }}
                                    ({{ $item->codigo }})
                                </td>

                                <td class="text-end">
                                    ${{ number_format($item->costo, 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    @if($esValorFijo)
                                        1
                                    @else
                                        {{ $item->cantidad }}
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
                            <th colspan="3" class="text-end">Total bruto</th>
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

</div>
@endsection