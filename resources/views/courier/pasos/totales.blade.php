@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');
    $pct = fn ($parte, $todo) => $todo > 0 ? round($parte * 100 / $todo, 1) : 0;

    /*
     * Tipos de pago que Operaciones paga cada mes y que todavía no se
     * cargan en el sistema. Se muestran apagados, para que se entienda
     * por qué el total es parcial.
     */
    $pendientes = array_values(array_diff(
        ['Ruta CV', 'Servicios', 'Visitas', 'Apoyo Alza', 'Especiales'],
        array_keys($resumenPago['por_tipo'] ?? [])
    ));
@endphp

<div class="co-page co-page-paso">

    @include('courier.partials.paso-cabeza')

        @if(session('calculoListo'))
            @php $c = session('calculoListo'); @endphp
            <div class="co-alert co-alert-ok" role="status">
                <strong>Cálculo terminado.</strong>
                {{ $n($c['pagar']) }} bultos se pagan y {{ $n($c['descontar']) }} quedan fuera.
                Apoyo Alza se armó con las reglas guardadas.
            </div>
        @endif

        @if($resumenPago && ! $resumenPago['cierre'])
            <form method="POST" action="{{ route('courier.calcular') }}" data-subir style="margin-bottom:1rem">
                @csrf
                <input type="hidden" name="periodo" value="{{ $periodo->codigo }}">
                <input type="hidden" name="volver" value="totales">
                <button type="submit" class="co-btn co-btn-muted" data-loading-text="Calculando…">Calcular de nuevo</button>
                <span class="co-note">Vuelve a calcular los bultos y Apoyo Alza con lo que esté cargado ahora.</span>
            </form>
        @endif

        @if(! $resumenPago)
            <p class="co-empty">Este período todavía no se ha calculado.</p>
        @else

            @if($resumenPago['cierre'])
                <p class="co-paso-frase" style="margin-bottom: 1rem;">
                    <span class="co-badge co-badge-accent">Cerrado</span>
                    El {{ $resumenPago['cierre']->closed_at->format('d-m-Y') }} se cerraron
                    <strong>{{ $n($resumenPago['cierre']->registros) }}</strong> pagos en
                    <strong>{{ $n($resumenPago['cierre']->ordenes_compra) }}</strong> órdenes de compra,
                    por <strong>${{ $n($resumenPago['cierre']->total) }}</strong>. El período ya no admite cambios.
                </p>
            @endif

            <p class="co-paso-dato">
                <span class="co-paso-numero">${{ $n($resumenPago['total']) }}</span>
                <span class="co-paso-unidad">a pagar</span>
            </p>

            <dl class="co-paso-cifras">
                <div class="co-paso-cifra">
                    <dt>Neto</dt>
                    <dd>${{ $n($resumenPago['neto']) }}</dd>
                </div>
                <div class="co-paso-cifra">
                    <dt>IVA</dt>
                    <dd>+${{ $n($resumenPago['iva']) }}</dd>
                </div>
                <div class="co-paso-cifra">
                    <dt>Retención</dt>
                    <dd>−${{ $n($resumenPago['retencion']) }}</dd>
                </div>
                <div class="co-paso-cifra">
                    <dt>Proveedores</dt>
                    <dd>{{ $n(count($resumenPago['por_proveedor'])) }}</dd>
                </div>
            </dl>

            <p class="co-paso-frase">
                La factura suma 19% de IVA, la boleta de honorarios descuenta 15,25% de retención
                y la factura exenta no lleva impuesto.
            </p>

            <div class="co-paso-bloque">
                <h2 class="co-paso-subtitulo">Por tipo de pago</h2>

                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Tipo de pago</th>
                                <th>Parte del neto</th>
                                <th class="is-num">Neto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resumenPago['por_tipo'] as $tipo => $monto)
                                <tr>
                                    <td class="is-strong">{{ $tipo }}</td>
                                    <td>
                                        <span class="co-barra is-ancha" role="img"
                                              aria-label="{{ $pct($monto, $resumenPago['neto']) }} por ciento del neto">
                                            <span style="width: {{ $pct($monto, $resumenPago['neto']) }}%"></span>
                                        </span>
                                        {{ $pct($monto, $resumenPago['neto']) }}%
                                    </td>
                                    <td class="is-num is-strong">${{ $n($monto) }}</td>
                                </tr>
                            @endforeach
                            @foreach($pendientes as $tipo)
                                <tr class="is-quieto">
                                    <td>{{ $tipo }}</td>
                                    <td colspan="2">Todavía no está en el sistema</td>
                                </tr>
                            @endforeach
                            <tr class="co-fila-total">
                                <td class="is-strong" colspan="2">Neto</td>
                                <td class="is-num is-strong">${{ $n($resumenPago['neto']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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
                                <th class="is-num">Retención</th>
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
                                    {{-- Un proveedor que sólo tiene Acuerdos no tiene bultos. --}}
                                    <td class="is-num {{ $proveedor['bultos'] > 0 ? '' : 'is-muted' }}">
                                        {{ $proveedor['bultos'] > 0 ? $n($proveedor['bultos']) : '—' }}
                                    </td>
                                    <td class="is-num">${{ $n($proveedor['neto']) }}</td>
                                    <td class="is-num is-muted">{{ $proveedor['iva'] > 0 ? '+$' . $n($proveedor['iva']) : '—' }}</td>
                                    <td class="is-num is-muted">{{ $proveedor['retencion'] > 0 ? '−$' . $n($proveedor['retencion']) : '—' }}</td>
                                    <td class="is-num is-strong">${{ $n($proveedor['total']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="co-fila-total">
                                <td class="is-strong" colspan="3">Total</td>
                                <td class="is-num">{{ $n($resumenPago['bultos_pagados']) }}</td>
                                <td class="is-num">${{ $n($resumenPago['neto']) }}</td>
                                <td class="is-num">+${{ $n($resumenPago['iva']) }}</td>
                                <td class="is-num">−${{ $n($resumenPago['retencion']) }}</td>
                                <td class="is-num is-strong">${{ $n($resumenPago['total']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        @endif

    @include('courier.partials.paso-pie')

</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-subir]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var boton = form.querySelector('button[type="submit"]');

            if (boton) {
                boton.disabled = true;
                boton.textContent = boton.getAttribute('data-loading-text') || 'Procesando…';
            }
        });
    });
})();
</script>
@endpush
