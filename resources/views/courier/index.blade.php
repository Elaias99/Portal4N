@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $revision = request()->boolean('nuevo') ? null : session('revisionGeolice');

    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');

    $resumenRevision = $revision['resumen'] ?? [];

    $comunasFuera = $revision['comunas_fuera_de_catalogo'] ?? [];
    $comunasFueraEjemplos = $revision['comunas_fuera_ejemplos'] ?? [];
    $sinConfiguracion = $revision['sin_configuracion'] ?? [];
    $estadosDesconocidos = $revision['estados_desconocidos'] ?? [];

    $bultosComunasFuera = array_sum($comunasFuera);
    $bultosSinConfiguracion = array_sum($sinConfiguracion);
    $bultosEstadosDesconocidos = array_sum($estadosDesconocidos);

    $hayAdvertencias =
        ! empty($comunasFuera)
        || ! empty($sinConfiguracion)
        || ! empty($estadosDesconocidos)
        || (($resumenRevision['fechas_no_reconocidas'] ?? 0) > 0)
        || (($resumenRevision['sin_comuna'] ?? 0) > 0);

    $periodoNombre = null;

    if (! empty($revision['periodo']) && preg_match('/^(\\d{4})(\\d{2})$/', $revision['periodo'], $m)) {
        $meses = [
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

        $periodoNombre = ($meses[(int) $m[2]] ?? $m[2]) . ' ' . $m[1];
    }

    $alertasPeriodo = $alertas ?? [];
    $estadosPeriodo = $alertasPeriodo['estados'] ?? [];
    $comunasFueraPeriodo = $alertasPeriodo['comunas_fuera_de_catalogo'] ?? [];
    $sinConfiguracionPeriodo = $alertasPeriodo['sin_configuracion'] ?? [];
    $ultimaImportacion = $importaciones->first();

    /*
     * Los tres grupos no se solapan: un bulto sin comuna no puede tener
     * comuna no reconocida, y la configuración sólo se busca cuando la
     * comuna sí se reconoció.
     */
    $pendientesTotal = ($alertasPeriodo['sin_comuna'] ?? 0)
        + ($alertasPeriodo['comunas_fuera_bultos'] ?? 0)
        + ($alertasPeriodo['sin_configuracion_bultos'] ?? 0);
@endphp

<style>
    .co-review-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .co-review-stat {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem 1.1rem;
        background: #fff;
    }

    .co-review-stat-label {
        display: block;
        color: #6b7280;
        font-size: .85rem;
        margin-bottom: .35rem;
    }

    .co-review-stat-value {
        display: block;
        font-size: 1.4rem;
        line-height: 1.1;
        font-weight: 700;
        color: #111827;
    }

    .co-review-status {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        padding: 1rem 1.1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
    }

    .co-review-status.is-ok {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .co-review-status.is-warn {
        background: #fffbeb;
        border: 1px solid #fde68a;
    }

    .co-review-status strong {
        display: block;
        margin-bottom: .2rem;
    }

    .co-review-status p {
        margin: 0;
        color: #4b5563;
    }

    .co-review-issues {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .co-review-issue {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        padding: 1rem 1.1rem;
    }

    .co-review-issue.is-ok {
        border-color: #bbf7d0;
    }

    .co-review-issue.is-warn {
        border-color: #fde68a;
    }

    .co-review-issue-title {
        margin: 0 0 .35rem;
        font-size: 1rem;
        font-weight: 700;
    }

    .co-review-issue-count {
        font-size: 1.75rem;
        line-height: 1;
        font-weight: 700;
        margin-bottom: .45rem;
    }

    .co-review-issue-text {
        margin: 0;
        color: #6b7280;
        font-size: .9rem;
    }

    .co-review-details {
        margin-top: .85rem;
    }

    .co-review-details summary {
        cursor: pointer;
        font-weight: 600;
    }

    .co-review-list {
        margin: .75rem 0 0;
        padding: 0;
        list-style: none;
        max-height: 240px;
        overflow: auto;
    }

    .co-review-list li {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .45rem 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .co-review-list li:last-child {
        border-bottom: 0;
    }

    .co-review-actions {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        margin-top: 1.25rem;
    }

    .co-review-actions-right {
        display: flex;
        gap: .75rem;
        align-items: center;
    }

    .co-review-disabled-note {
        color: #6b7280;
        font-size: .85rem;
        margin: .5rem 0 0;
        text-align: right;
    }

    @media (max-width: 900px) {
        .co-review-summary,
        .co-review-issues {
            grid-template-columns: 1fr;
        }

        .co-review-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .co-review-actions-right {
            flex-direction: column;
            align-items: stretch;
        }

        .co-review-disabled-note {
            text-align: left;
        }
    }
</style>

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Courier · Pago del mes',
        'subtitulo' => $revision
            ? 'Revisa el resultado antes de decidir si el archivo debe incorporarse al proceso.'
            : (($alertasPeriodo['total'] ?? 0) > 0
                ? "{$periodo->nombre} tiene datos cargados. Revisa primero su estado antes de continuar."
                : 'Carga la descarga de Geolice para comenzar el proceso de pago Courier.'),
        'volverRuta' => route('cobranzas.general'),
        'volverTexto' => 'Volver al panel de Finanzas',
        'meta' => $revision
            ? 'Revisión del archivo'
            : (($alertasPeriodo['total'] ?? 0) > 0 ? 'Datos del período' : 'Inicio del proceso'),
    ])

    @if($errors->any())
        <div class="co-alert co-alert-danger" role="alert">
            <strong>No se pudo revisar el archivo.</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($periodo && !$revision)
        @include('courier.partials.periodo-nav', ['activo' => 'resumen'])

        <div class="co-chips" style="margin-bottom: 1rem;">
            <span class="co-chip">Estado: <strong>{{ $periodo->estado === 'cerrado' ? 'Cerrado' : 'Abierto' }}</strong></span>
            <span class="co-chip"><strong>{{ $n($alertasPeriodo['total'] ?? 0) }}</strong> bultos cargados</span>
        </div>

        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Datos cargados</h2>
                    <p class="co-note">La descarga de Geolice ya está guardada. Estas cifras aún no representan pagos calculados.</p>
                </div>
            </div>

            <div class="co-region-body">
                <div class="co-review-summary">
                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Bultos del período</span>
                        <span class="co-review-stat-value">{{ $n($alertasPeriodo['total'] ?? 0) }}</span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Archivos importados</span>
                        <span class="co-review-stat-value">{{ $n($importaciones->count()) }}</span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Última carga</span>
                        <span class="co-review-stat-value" style="font-size: 1rem;">
                            {{ $ultimaImportacion?->archivo ?? '—' }}
                        </span>
                        @if($ultimaImportacion)
                            <p class="co-note" style="margin-top: .45rem;">
                                {{ $ultimaImportacion->created_at->format('d-m-Y H:i') }} · {{ $ultimaImportacion->usuario?->name ?? 'Terminal' }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Dónde seguir</h2>
                    <p class="co-note">El detalle de cada tema vive en su propia pantalla, para poder mirarlo con calma.</p>
                </div>
            </div>

            <div class="co-region-body">
                <div class="co-accesos">
                    <a class="co-acceso" href="{{ route('courier.distribucion', ['periodo' => $periodo->codigo]) }}">
                        <span class="co-acceso-valor">{{ $n($alertasPeriodo['total'] ?? 0) }}</span>
                        <span class="co-acceso-titulo">Distribución por agente</span>
                        <span class="co-acceso-texto">
                            Cuántos bultos le corresponden a cada agente, agrupados por zona.
                        </span>
                    </a>

                    <a class="co-acceso {{ $pendientesTotal > 0 ? 'is-warn' : 'is-ok' }}"
                       href="{{ route('courier.pendientes', ['periodo' => $periodo->codigo]) }}">
                        <span class="co-acceso-valor">{{ $n($pendientesTotal) }}</span>
                        <span class="co-acceso-titulo">Bultos con algo pendiente</span>
                        <span class="co-acceso-texto">
                            Sin comuna, con comuna no reconocida o sin configuración de pago.
                        </span>
                    </a>

                    <a class="co-acceso" href="#importar">
                        <span class="co-acceso-valor">{{ $n($importaciones->count()) }}</span>
                        <span class="co-acceso-titulo">Archivos cargados</span>
                        <span class="co-acceso-texto">
                            Cargar otra descarga de Geolice sobre este mismo período.
                        </span>
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- ====== LO QUE SE PUEDE PAGAR ====== --}}
    @if($periodo && !$revision)
        <section class="co-region co-pago-resumen">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Lo que se puede pagar hoy</h2>
                    <p class="co-note">
                        @if($resumenPago)
                            Bultos que cumplen todas las reglas del catálogo: comuna reconocida, configuración de pago
                            y tarifa. Calculado el {{ \Carbon\Carbon::parse($resumenPago['calculado_at'])->format('d-m-Y H:i') }}.
                        @elseif(($alertasPeriodo['total'] ?? 0) > 0)
                            El período todavía no se ha calculado.
                        @else
                            El período está vacío.
                        @endif
                    </p>
                </div>
                <div class="co-review-actions-right">
                    @if($resumenPago)
                        <a href="{{ route('courier.pago', ['periodo' => $periodo->codigo]) }}" class="co-btn co-btn-primary co-btn-sm">
                            Ver detalle por proveedor
                        </a>
                    @endif

                    @if(($alertasPeriodo['total'] ?? 0) > 0)
                        <form method="POST" action="{{ route('courier.calcular') }}" data-upload>
                            @csrf
                            <input type="hidden" name="periodo" value="{{ $periodo->codigo }}">
                            <button
                                type="submit"
                                class="co-btn {{ $resumenPago ? 'co-btn-muted' : 'co-btn-primary' }} co-btn-sm"
                                data-submit
                                data-loading-text="Calculando…"
                            >
                                {{ $resumenPago ? 'Volver a calcular' : 'Calcular pago del período' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="co-region-body">
                @if(! $resumenPago)
                    <p class="co-empty">
                        @if(($alertasPeriodo['total'] ?? 0) > 0)
                            Los bultos están cargados, pero falta aplicarles la cadena de pago:
                            comuna, agente, configuración, kilos y tarifa.
                            Aprieta <strong>Calcular pago del período</strong> para verlos convertidos en montos.
                        @else
                            Todavía no hay bultos en este período. Carga la descarga de Geolice para empezar.
                        @endif
                    </p>
                @else
                    <div class="co-totales">
                        <div class="co-total is-principal">
                            <span class="co-total-label">Total a pagar con IVA</span>
                            <span class="co-total-valor">${{ $n($resumenPago['total']) }}</span>
                            <span class="co-total-detalle">
                                {{ $n($resumenPago['bultos_pagados']) }} bultos de {{ $n($resumenPago['bultos_calculados']) }}
                            </span>
                        </div>

                        <div class="co-total">
                            <span class="co-total-label">Neto</span>
                            <span class="co-total-valor">${{ $n($resumenPago['neto']) }}</span>
                            <span class="co-total-detalle">Suma del valor de cada bulto</span>
                        </div>

                        <div class="co-total">
                            <span class="co-total-label">IVA</span>
                            <span class="co-total-valor">${{ $n($resumenPago['iva']) }}</span>
                            <span class="co-total-detalle">Sólo a los proveedores que emiten factura</span>
                        </div>

                        <div class="co-total {{ $resumenPago['bloqueados'] > 0 ? 'is-warn' : '' }}">
                            <span class="co-total-label">Bultos sin poder pagar</span>
                            <span class="co-total-valor">{{ $n($resumenPago['bloqueados']) }}</span>
                            <span class="co-total-detalle">Les falta una regla, no una decisión de pago</span>
                        </div>
                    </div>

                    @if($resumenPago['por_tipo'] !== [])
                        <div class="co-chips" style="margin: 1rem 0 0;">
                            @foreach($resumenPago['por_tipo'] as $tipo => $monto)
                                <span class="co-chip">{{ $tipo }}: <strong>${{ $n($monto) }}</strong></span>
                            @endforeach
                            @foreach($resumenPago['por_zona'] as $zona)
                                <span class="co-chip">{{ $zona['zona'] }}: <strong>${{ $n($zona['neto']) }}</strong></span>
                            @endforeach
                        </div>
                    @endif

                    <p class="co-note" style="margin-top: 1rem;">
                        Este monto cubre los pagos que salen de la descarga de Geolice (Variables y Lanas).
                        Los pagos que Operaciones lleva aparte —acuerdos, servicios, ruta CV, visitas y otros— todavía
                        no están en el sistema y no se suman acá.
                    </p>
                @endif
            </div>
        </section>
    @endif

    @if(!$revision)
        <section class="co-region co-upload" id="importar">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">
                        {{ ($alertasPeriodo['total'] ?? 0) > 0 ? 'Revisar otra descarga de Geolice' : 'Comenzar pago Courier' }}
                    </h2>
                    <p class="co-note">
                        Selecciona el mes de pago y carga la descarga de Geolice correspondiente.
                        En esta etapa el archivo sólo se revisa; todavía no se guardan bultos.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('courier.revisar-geolice') }}"
                enctype="multipart/form-data"
                class="co-region-body co-upload-form"
                data-upload
            >
                @csrf

                <div class="co-upload-row">
                    <div class="co-field">
                        <label for="mes">Mes de pago</label>
                        <input
                            type="month"
                            id="mes"
                            name="periodo"
                            class="form-control"
                            value="{{ old('periodo', $mesSugerido) }}"
                            required
                        >
                    </div>

                    <div class="co-field co-upload-file">
                        <label for="archivo">Archivo de Geolice</label>
                        <input
                            type="file"
                            id="archivo"
                            name="archivo"
                            class="form-control"
                            accept=".xlsx,.csv"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="co-btn co-btn-primary"
                        data-submit
                        data-loading-text="Revisando…"
                    >
                        Revisar archivo
                    </button>
                </div>

                <p class="co-note">
                    Usa la descarga de Geolice en formato <code>.xlsx</code> o <code>.csv</code>.
                </p>

                <p class="co-upload-progress" data-progress hidden>
                    Revisando el archivo… este proceso puede tardar unos minutos. No cierres esta pestaña.
                </p>
            </form>
        </section>
    @else

        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Resultado de la revisión</h2>
                    <p class="co-note">
                        El archivo fue leído y analizado. Esta revisión no confirma todavía la importación.
                    </p>
                </div>
            </div>

            <div class="co-region-body">

                <div class="co-review-status {{ $hayAdvertencias ? 'is-warn' : 'is-ok' }}">
                    <div>
                        <strong>
                            {{ $hayAdvertencias
                                ? 'Archivo revisado con observaciones'
                                : 'Archivo revisado correctamente' }}
                        </strong>

                        <p>
                            {{ $hayAdvertencias
                                ? 'Se encontraron datos que conviene revisar antes de confirmar la importación.'
                                : 'No se detectaron observaciones en los controles disponibles actualmente.' }}
                        </p>
                    </div>
                </div>

                <div class="co-review-summary">
                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Archivo</span>
                        <span class="co-review-stat-value" style="font-size: 1rem;">
                            {{ $revision['archivo'] ?? '—' }}
                        </span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Período de pago</span>
                        <span class="co-review-stat-value" style="font-size: 1rem;">
                            {{ $periodoNombre ?? ($revision['periodo'] ?? '—') }}
                        </span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Bultos encontrados</span>
                        <span class="co-review-stat-value">
                            {{ $n($resumenRevision['filas'] ?? 0) }}
                        </span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Bultos nuevos</span>
                        <span class="co-review-stat-value">
                            {{ $n($resumenRevision['nuevos'] ?? 0) }}
                        </span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Ya existentes en este período</span>
                        <span class="co-review-stat-value">
                            {{ $n(
                                $resumenRevision['ya_en_el_periodo']
                                    ?? (($resumenRevision['actualizados'] ?? 0)
                                        + ($resumenRevision['sin_cambio'] ?? 0))
                            ) }}
                        </span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Encontrados en período anterior</span>
                        <span class="co-review-stat-value">
                            {{ $n($resumenRevision['de_periodo_anterior'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="co-region">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Observaciones</h2>
                    <p class="co-note">
                        Aquí sólo se muestran los puntos que pueden necesitar revisión antes de continuar.
                    </p>
                </div>
            </div>

            <div class="co-region-body">
                <div class="co-review-issues">

                    <article class="co-review-issue {{ empty($comunasFuera) ? 'is-ok' : 'is-warn' }}">
                        <h3 class="co-review-issue-title">Comunas fuera del catálogo</h3>

                        <div class="co-review-issue-count">
                            {{ $n(count($comunasFuera)) }}
                        </div>

                        <p class="co-review-issue-text">
                            @if(empty($comunasFuera))
                                Todas las comunas informadas tienen una cobertura conocida.
                            @else
                                {{ $n($bultosComunasFuera) }} bultos usan una comuna que no existe actualmente en el catálogo.
                            @endif
                        </p>

                        @if(!empty($comunasFuera))
                            <details class="co-review-details">
                                <summary>Ver detalle</summary>

                                <ul class="co-review-list">
                                    @foreach(array_slice($comunasFuera, 0, 30, true) as $comuna => $cantidad)
                                        <li>
                                            <span>
                                                <span class="co-comuna-nombre">{{ $comuna }}</span>
                                                @foreach($comunasFueraEjemplos[$comuna] ?? [] as $ejemplo)
                                                    <span class="co-comuna-ejemplo">
                                                        {{ $ejemplo['comerciante'] }} · {{ $ejemplo['direccion'] ?? 'sin dirección' }}
                                                    </span>
                                                @endforeach
                                            </span>
                                            <strong>{{ $n($cantidad) }}</strong>
                                        </li>
                                    @endforeach
                                </ul>

                                @if(count($comunasFuera) > 30)
                                    <p class="co-note">
                                        Se muestran 30 de {{ $n(count($comunasFuera)) }} comunas.
                                    </p>
                                @endif
                            </details>
                        @endif
                    </article>

                    <article class="co-review-issue {{ empty($sinConfiguracion) ? 'is-ok' : 'is-warn' }}">
                        <h3 class="co-review-issue-title">Sin configuración de pago</h3>

                        <div class="co-review-issue-count">
                            {{ $n(count($sinConfiguracion)) }}
                        </div>

                        <p class="co-review-issue-text">
                            @if(empty($sinConfiguracion))
                                Todas las combinaciones conocidas tienen configuración de pago.
                            @else
                                {{ $n($bultosSinConfiguracion) }} bultos no encuentran una combinación agente + cliente + servicio.
                            @endif
                        </p>

                        @if(!empty($sinConfiguracion))
                            <details class="co-review-details">
                                <summary>Ver detalle</summary>

                                <ul class="co-review-list">
                                    @foreach(array_slice($sinConfiguracion, 0, 30, true) as $combinacion => $cantidad)
                                        <li>
                                            <span>{{ $combinacion }}</span>
                                            <strong>{{ $n($cantidad) }}</strong>
                                        </li>
                                    @endforeach
                                </ul>

                                @if(count($sinConfiguracion) > 30)
                                    <p class="co-note">
                                        Se muestran 30 de {{ $n(count($sinConfiguracion)) }} combinaciones.
                                    </p>
                                @endif
                            </details>
                        @endif
                    </article>

                    <article class="co-review-issue {{ empty($estadosDesconocidos) ? 'is-ok' : 'is-warn' }}">
                        <h3 class="co-review-issue-title">Estados fuera del catálogo</h3>

                        <div class="co-review-issue-count">
                            {{ $n(count($estadosDesconocidos)) }}
                        </div>

                        <p class="co-review-issue-text">
                            @if(empty($estadosDesconocidos))
                                Todos los estados informados existen en el catálogo.
                            @else
                                {{ $n($bultosEstadosDesconocidos) }} bultos usan estados que todavía no tienen una regla conocida.
                            @endif
                        </p>

                        @if(!empty($estadosDesconocidos))
                            <details class="co-review-details">
                                <summary>Ver detalle</summary>

                                <ul class="co-review-list">
                                    @foreach(array_slice($estadosDesconocidos, 0, 30, true) as $estado => $cantidad)
                                        <li>
                                            <span>{{ $estado }}</span>
                                            <strong>{{ $n($cantidad) }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </article>

                </div>

                <div class="co-review-summary">
                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Sin comuna de destino</span>
                        <span class="co-review-stat-value">
                            {{ $n($resumenRevision['sin_comuna'] ?? 0) }}
                        </span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Sin peso declarado</span>
                        <span class="co-review-stat-value">
                            {{ $n($resumenRevision['sin_peso_declarado'] ?? 0) }}
                        </span>
                    </div>

                    <div class="co-review-stat">
                        <span class="co-review-stat-label">Fechas no reconocidas</span>
                        <span class="co-review-stat-value">
                            {{ $n($resumenRevision['fechas_no_reconocidas'] ?? 0) }}
                        </span>
                    </div>
                </div>

                <div class="co-review-actions">
                    <a
                        href="{{ route('courier.index', ['nuevo' => 1]) }}"
                        class="co-btn co-btn-muted"
                    >
                        Elegir otro archivo
                    </a>

                    <div>
                        <form
                            method="POST"
                            action="{{ route('courier.confirmar-geolice') }}"
                            data-upload
                        >
                            @csrf
                            <div class="co-review-actions-right">
                                <button
                                    type="submit"
                                    class="co-btn co-btn-primary"
                                    data-submit
                                    data-loading-text="Guardando…"
                                >
                                    Confirmar importación
                                </button>
                            </div>

                            <p class="co-review-disabled-note" data-progress hidden>
                                Guardando los bultos… no cierres esta pestaña.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </section>

    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-upload]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var boton = form.querySelector('[data-submit]');
            var aviso = form.querySelector('[data-progress]');

            if (boton) {
                boton.disabled = true;
                boton.textContent = boton.getAttribute('data-loading-text') || 'Revisando…';
            }

            if (aviso) {
                aviso.hidden = false;
            }
        });
    });
})();
</script>
@endpush
