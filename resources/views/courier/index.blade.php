@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $revision = request()->boolean('nuevo') ? null : session('revisionGeolice');

    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');

    $resumenRevision = $revision['resumen'] ?? [];

    $comunasFuera = $revision['comunas_fuera_de_catalogo'] ?? [];
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
            : 'Carga la descarga de Geolice para comenzar el proceso de pago Courier.',
        'volverRuta' => route('cobranzas.general'),
        'volverTexto' => 'Volver al panel de Finanzas',
        'meta' => $revision ? 'Revisión del archivo' : 'Inicio del proceso',
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

    @if(!$revision)
        <section class="co-region co-upload" id="importar">
            <div class="co-region-head">
                <div>
                    <h2 class="co-region-title">Comenzar pago Courier</h2>
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
                                ($resumenRevision['actualizados'] ?? 0)
                                + ($resumenRevision['sin_cambio'] ?? 0)
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
                                            <span>{{ $comuna }}</span>
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
                        <div class="co-review-actions-right">
                            <button
                                type="button"
                                class="co-btn co-btn-primary"
                                disabled
                                title="La confirmación se habilitará en el siguiente paso."
                            >
                                Confirmar importación
                            </button>
                        </div>

                        <p class="co-review-disabled-note">
                            Primera prueba: este botón todavía no guarda información.
                        </p>
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
