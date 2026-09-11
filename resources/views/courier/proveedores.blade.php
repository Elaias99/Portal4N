@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $mascara = function (?string $cuenta): string {
        $cuenta = trim((string) $cuenta);

        if ($cuenta === '') {
            return '—';
        }

        return mb_strlen($cuenta) <= 4
            ? $cuenta
            : '•••• ' . mb_substr($cuenta, -4);
    };
@endphp

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Proveedores',
        'subtitulo' => 'A quién se le paga cada combinación de agente + repartidor, con qué documento y a qué cuenta. Es la hoja DatosProveedores.',
    ])

    @include('courier.partials.nav', ['activo' => 'proveedores'])

    <section class="co-region">
        <form method="GET" action="{{ route('courier.proveedores') }}" class="co-filters" role="search">
            <div class="co-field co-span-5">
                <label for="q">Agente, repartidor, razón social o RUT</label>
                <input type="search" id="q" name="q" class="form-control"
                       value="{{ $buscar }}" placeholder="4N RM, Conejeros, 77.458…" autocomplete="off">
            </div>
            <div class="co-field co-span-3">
                <label for="tipo">Documento</label>
                <select id="tipo" name="tipo" class="form-select">
                    <option value="">Todos</option>
                    @foreach($tiposDocumento as $tipo)
                        <option value="{{ $tipo }}" @selected($tipoSeleccionado === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="co-span-2">
                <button type="submit" class="co-btn co-btn-primary w-100">Buscar</button>
            </div>
            @if($buscar !== '' || $tipoSeleccionado !== '')
                <div class="co-span-2">
                    <a href="{{ route('courier.proveedores') }}" class="co-btn co-btn-muted w-100">Limpiar</a>
                </div>
            @endif
        </form>

        <div class="co-region-head">
            <h2 class="co-region-title">
                {{ number_format($proveedores->total(), 0, ',', '.') }} proveedores
            </h2>
            <span class="co-region-meta">
                {{ number_format($resumen['proveedores']['factura'], 0, ',', '.') }} con factura ·
                {{ number_format($resumen['proveedores']['boleta'], 0, ',', '.') }} con boleta ·
                {{ number_format($resumen['proveedores']['sin_documento'], 0, ',', '.') }} sin documento
            </span>
        </div>

        <div class="co-region-body is-flush">
            @if($proveedores->isEmpty())
                <div class="co-empty">Ningún proveedor coincide con el filtro.</div>
            @else
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Repartidor</th>
                                <th>Razón social</th>
                                <th>RUT</th>
                                <th>Documento</th>
                                <th>Banco</th>
                                <th>Cuenta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($proveedores as $p)
                                <tr>
                                    <td class="is-strong">{{ $p->operador }}</td>
                                    <td>{{ $p->usuario === '0' ? '—' : $p->usuario }}</td>
                                    <td>{{ $p->razon_social }}</td>
                                    <td class="is-mono">{{ $p->rut ?? '—' }}</td>
                                    <td>
                                        @if($p->tipo_documento === 'Factura')
                                            <span class="co-badge co-badge-accent">Factura</span>
                                        @elseif(str_starts_with($p->tipo_documento, 'Boleta'))
                                            <span class="co-badge co-badge-neutral">{{ $p->tipo_documento }}</span>
                                        @elseif($p->tipo_documento === 'Sin Documento')
                                            <span class="co-badge co-badge-zona-none">Sin documento</span>
                                        @else
                                            <span class="co-badge co-badge-neutral">{{ $p->tipo_documento }}</span>
                                        @endif
                                    </td>
                                    <td class="is-muted">
                                        {{ $p->banco ?? '—' }}
                                        @if($p->tipo_cuenta)
                                            <span style="font-size:0.7rem"> · {{ $p->tipo_cuenta }}</span>
                                        @endif
                                    </td>
                                    <td class="is-mono is-muted">{{ $mascara($p->nro_cuenta) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="co-pagination">
                    <span>
                        Mostrando {{ $proveedores->firstItem() }}–{{ $proveedores->lastItem() }} de {{ number_format($proveedores->total(), 0, ',', '.') }}
                    </span>
                    {{ $proveedores->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
        <div class="co-region-body" style="border-top:1px solid var(--co-line-soft)">
            <p class="co-note">
                El IVA (19 %) se agrega sólo a quienes emiten <strong>Factura</strong>; Factura Exenta, Boleta y Sin Documento van sin IVA.
                El número de cuenta se muestra enmascarado en esta pantalla de consulta.
            </p>
        </div>
    </section>

</div>
@endsection
