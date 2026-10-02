@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');
@endphp

<div class="co-page co-page-paso">

    @include('courier.partials.paso-cabeza')

        @if(! $resumenPago)
            <p class="co-empty">Este período todavía no se ha calculado.</p>
        @else

            <p class="co-paso-dato">
                <span class="co-paso-numero">${{ $n($resumenPago['total']) }}</span>
                <span class="co-paso-unidad">a pagar, con IVA</span>
            </p>

            <p class="co-paso-frase">
                Son <strong>${{ $n($resumenPago['neto']) }}</strong> netos más
                <strong>${{ $n($resumenPago['iva']) }}</strong> de IVA, repartidos entre
                <strong>{{ $n(count($resumenPago['por_proveedor'])) }}</strong> proveedores,
                por {{ $n($resumenPago['bultos_pagados']) }} bultos.
                El 19% se agrega sólo a quienes emiten factura.
            </p>

            @if(($resumenPago['por_tipo'] ?? []) !== [])
                {{-- Neto por tipo de pago: la misma lectura que las columnas de ResumenPagos. --}}
                <div class="co-chips" style="margin-top: 1rem;">
                    @foreach($resumenPago['por_tipo'] as $tipo => $monto)
                        <span class="co-chip">{{ $tipo }}: <strong>${{ $n($monto) }}</strong></span>
                    @endforeach
                </div>
            @endif

            <div class="co-paso-bloque">
                <h2 class="co-paso-subtitulo">A quién se le paga</h2>

                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Razón social</th>
                                <th>RUT</th>
                                <th>Documento</th>
                                <th class="is-num">Bultos</th>
                                <th class="is-num">Neto</th>
                                <th class="is-num">IVA</th>
                                <th class="is-num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resumenPago['por_proveedor'] as $proveedor)
                                <tr class="{{ $proveedor['razon_social'] === null ? 'is-selected' : '' }}">
                                    <td class="is-strong">{{ $proveedor['razon_social'] ?? 'Sin proveedor identificado' }}</td>
                                    <td class="is-mono">{{ $proveedor['rut'] ?? '—' }}</td>
                                    <td>
                                        @if($proveedor['tipo_documento'])
                                            <span class="co-badge {{ $proveedor['tipo_documento'] === 'Factura' ? 'co-badge-accent' : 'co-badge-neutral' }}">
                                                {{ $proveedor['tipo_documento'] }}
                                            </span>
                                        @else
                                            <span class="co-badge co-badge-no">Sin definir</span>
                                        @endif
                                    </td>
                                    <td class="is-num">{{ $n($proveedor['bultos']) }}</td>
                                    <td class="is-num">${{ $n($proveedor['neto']) }}</td>
                                    <td class="is-num is-muted">{{ $proveedor['iva'] > 0 ? '$' . $n($proveedor['iva']) : '—' }}</td>
                                    <td class="is-num is-strong">${{ $n($proveedor['total']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="co-fila-total">
                                <td class="is-strong" colspan="3">Total</td>
                                <td class="is-num">{{ $n($resumenPago['bultos_pagados']) }}</td>
                                <td class="is-num">${{ $n($resumenPago['neto']) }}</td>
                                <td class="is-num">${{ $n($resumenPago['iva']) }}</td>
                                <td class="is-num is-strong">${{ $n($resumenPago['total']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="co-note">
                Este total cubre sólo los pagos que salen de la descarga de Geolice. Los acuerdos, servicios,
                ruta CV, visitas, apoyo alza, fijo base y especiales que Operaciones lleva en hojas aparte
                todavía no están en el sistema.
            </p>

        @endif

    @include('courier.partials.paso-pie')

</div>
@endsection
