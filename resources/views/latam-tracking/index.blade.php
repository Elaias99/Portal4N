<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATAM Tracking</title>

    @viteReactRefresh
    @vite([
        'resources/css/app.css',
        'resources/css/appcustom.css',
        'resources/sass/app.scss',
        'resources/js/app.js',
        'resources/js/react/latam-tracking/main.jsx',
    ])
</head>
<body class="latam-page">
    @php
        $currentPrefijo = request('prefijo', request('filter_prefijo', ''));
        $currentCodigoTracking = request('codigo_tracking', request('filter_codigo_tracking', ''));
        $currentDestino = request('destino', request('filter_destino', ''));
        $currentFechaProceso = request('fecha_proceso', request('filter_fecha_proceso', ''));

        // Nuevas variables con fallback para no romper la vista
        $trackingEstadoActual = $trackingEstadoActual ?? null;
        $trackingConsulta = $trackingConsulta ?? null;
        $trackingCambioDetectado = $trackingCambioDetectado ?? false;
        $trackingFallbackDisponible = $trackingFallbackDisponible ?? false;
        $trackingPersisted = $trackingPersisted ?? false;
    @endphp

    <div class="container py-4 py-lg-5">
        <div class="mb-4">
            <h1 class="mb-1">LATAM Tracking</h1>
            <p class="text-muted mb-0">
                Consulta interna de códigos almacenados y estado LATAM.
            </p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <strong>Revisa estos campos:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm latam-filter-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <h2 class="h5 mb-0">Filtrar registros</h2>

                    @if($currentFechaProceso)
                        <span class="latam-soft-badge">
                            Fecha proceso: {{ $currentFechaProceso }}
                        </span>
                    @endif
                </div>

                <form method="GET" action="{{ route('latam.tracking.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="prefijo" class="form-label">Prefijo</label>
                            <input
                                type="text"
                                id="prefijo"
                                name="prefijo"
                                value="{{ $currentPrefijo }}"
                                class="form-control"
                                placeholder="Ej: 972"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="codigo_tracking" class="form-label">Código tracking</label>
                            <input
                                type="text"
                                id="codigo_tracking"
                                name="codigo_tracking"
                                value="{{ $currentCodigoTracking }}"
                                class="form-control"
                                placeholder="Ej: 03574362"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="destino" class="form-label">Destino</label>
                            <input
                                type="text"
                                id="destino"
                                name="destino"
                                value="{{ $currentDestino }}"
                                class="form-control"
                                placeholder="Ej: SCL ARICA"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="fecha_proceso" class="form-label">Fecha proceso</label>
                            <input
                                type="date"
                                id="fecha_proceso"
                                name="fecha_proceso"
                                value="{{ $currentFechaProceso }}"
                                class="form-control"
                            >
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-dark px-4">Filtrar</button>
                        <a href="{{ route('latam.tracking.index') }}" class="btn btn-outline-secondary">Limpiar filtros</a>
                    </div>

                    <div class="form-text mt-2">
                        Si no indicas filtros, el sistema trabaja con la fecha del día.
                    </div>
                </form>
            </div>
        </div>

        <div id="latam-tracking-workspace"></div>

        <div class="mt-4">
            <a href="{{ url('/empleados') }}" class="btn btn-outline-secondary">
                Volver a empleados
            </a>
        </div>
    </div>

    <script type="application/json" id="latam-tracking-workspace-props">{!!
        json_encode([
            'rows' => $rows,
            'actions' => [
                'processUrl' => route('latam.tracking.process'),
                'csrfToken' => csrf_token(),
                'currentPrefijo' => $currentPrefijo,
                'currentCodigoTracking' => $currentCodigoTracking,
                'currentDestino' => $currentDestino,
                'currentFechaProceso' => $currentFechaProceso,
            ],
            'trackingLookup' => $trackingLookup,
            'trackingResult' => $trackingResult,
            'trackingError' => $trackingError,

            // Nuevas props para fallback / snapshot
            'trackingEstadoActual' => $trackingEstadoActual,
            'trackingConsulta' => $trackingConsulta,
            'trackingCambioDetectado' => $trackingCambioDetectado,
            'trackingFallbackDisponible' => $trackingFallbackDisponible,
            'trackingPersisted' => $trackingPersisted,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    !!}</script>

    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('.js-track-form');
            if (!form) return;

            const button = form.querySelector('.js-track-submit');
            if (!button) return;

            button.disabled = true;
            button.textContent = 'Consultando...';
        });
    </script>
</body>
</html>