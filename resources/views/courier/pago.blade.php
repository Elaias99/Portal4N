@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    use App\Services\Courier\CourierCalculoService;

    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');
@endphp

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Courier · Pago a proveedores',
        'subtitulo' => 'Cuánto se le paga a cada proveedor por los bultos del período, con su documento e IVA.',
        'volverRuta' => route('courier.index'),
        'volverTexto' => 'Volver a Pago del mes',
        'meta' => 'Sólo pagos que salen de la descarga de Geolice',
    ])

    @include('courier.partials.nav', ['activo' => 'index'])

    @include('courier.partials.periodo-nav', ['activo' => 'pago'])

    @if(session('calculoListo'))
        @php $c = session('calculoListo'); @endphp
        <div class="co-alert co-alert-ok" role="status">
            <strong>Cálculo terminado.</strong>
            Se revisaron {{ $n($c['bultos']) }} bultos: {{ $n($c['pagar']) }} se pagan y
            {{ $n($c['descontar']) }} quedan fuera, cada uno con su motivo.
        </div>
    @endif

    @if(! $periodo || ! $resumenPago)
        <section class="co-region">
            <div class="co-region-body">
                <p class="co-empty">
                    Este período todavía no se ha calculado, así que no hay montos que mostrar.
                    <a href="{{ route('courier.index') }}">Vuelve al resumen del período</a> para ver qué falta.
                </p>
            </div>
        </section>
    @else

        <div class="co-totales" style="margin-bottom: 1rem;">
            <div class="co-total is-principal">
                <span class="co-total-label">Total a pagar con IVA</span>
                <span class="co-total-valor">${{ $n($resumenPago['total']) }}</span>
                <span class="co-total-detalle">{{ $n($resumenPago['bultos_pagados']) }} bultos</span>
            </div>
            <div class="co-total">
                <span class="co-total-label">Neto</span>
                <span class="co-total-valor">${{ $n($resumenPago['neto']) }}</span>
                <span class="co-total-detalle">Antes de IVA</span>
            </div>
            <div class="co-total">
                <span class="co-total-label">IVA</span>
                <span class="co-total-valor">${{ $n($resumenPago['iva']) }}</span>
                <span class="co-total-detalle">19% sólo a quienes emiten factura</span>
            </div>
            <div class="co-total">
                <span class="co-total-label">Proveedores</span>
                <span class="co-total-valor">{{ $n(count($resumenPago['por_proveedor'])) }}</span>
                <span class="co-total-detalle">Reciben pago este período</span>
            </div>
        </div>

        {{-- ====== POR ZONA Y TIPO DE PAGO ====== --}}
        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Por zona y tipo de pago</h2>
                    <p class="co-note">La misma lectura que la hoja ResumenPagos de Operaciones, con las columnas que hoy calcula el sistema.</p>
                </div>
            </div>

            <div class="co-table-wrap">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Zona</th>
                            <th class="is-num">Bultos</th>
                            <th class="is-num">Neto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resumenPago['por_zona'] as $zona)
                            <tr>
                                <td class="is-strong">{{ $zona['zona'] }}</td>
                                <td class="is-num">{{ $n($zona['bultos']) }}</td>
                                <td class="is-num">${{ $n($zona['neto']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="co-fila-total">
                            <td class="is-strong">Total</td>
                            <td class="is-num">{{ $n($resumenPago['bultos_pagados']) }}</td>
                            <td class="is-num">${{ $n($resumenPago['neto']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="co-region-body">
                <div class="co-chips">
                    @foreach($resumenPago['por_tipo'] as $tipo => $monto)
                        <span class="co-chip">{{ $tipo }}: <strong>${{ $n($monto) }}</strong></span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ====== POR PROVEEDOR ====== --}}
        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Por proveedor</h2>
                    <p class="co-note">
                        A quién se le paga, con qué documento y cuánto. El IVA se agrega sólo cuando el documento es
                        exactamente «Factura».
                    </p>
                </div>
            </div>

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
                                <td class="is-strong">
                                    {{ $proveedor['razon_social'] ?? 'Sin proveedor identificado' }}
                                </td>
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
        </section>

        {{-- ====== POR QUÉ NO SE PAGAN LOS DEMÁS ====== --}}
        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Por qué no se pagan los demás</h2>
                    <p class="co-note">
                        Cada bulto que queda fuera dice su motivo. Los que necesitan una decisión de Operaciones están
                        marcados.
                    </p>
                </div>
                <a href="{{ route('courier.pendientes', ['periodo' => $periodo->codigo]) }}" class="co-btn co-btn-muted co-btn-sm">
                    Ver los pendientes
                </a>
            </div>

            <div class="co-table-wrap">
                <table class="co-table">
                    <thead>
                        <tr>
                            <th>Motivo</th>
                            <th class="is-num">Bultos</th>
                            <th>Qué significa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $necesitanDecision = ['sin_comuna', 'comuna_desconocida', 'sin_rut_proveedor', 'sin_configuracion', 'llave_ambigua', 'sin_tabla', 'sin_tarifa', 'configuracion_revisar', 'peumo_sin_guia', 'peumo_sin_tarifa'];
                        @endphp
                        @foreach($resumenPago['motivos'] as $motivo => $cantidad)
                            <tr>
                                <td class="is-strong">
                                    {{ CourierCalculoService::MOTIVOS[$motivo] ?? $motivo }}
                                </td>
                                <td class="is-num">{{ $n($cantidad) }}</td>
                                <td>
                                    @if(in_array($motivo, $necesitanDecision, true))
                                        <span class="co-badge co-badge-revisar">Necesita definición de Operaciones</span>
                                    @else
                                        <span class="is-muted">Regla ya definida</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="co-note">
            Este total cubre sólo los pagos que salen de la descarga de Geolice (Variables y Lanas).
            Los acuerdos, servicios, ruta CV, visitas, apoyo alza, fijo base y especiales que Operaciones lleva en hojas
            aparte todavía no están en el sistema.
        </p>

    @endif

</div>
@endsection
