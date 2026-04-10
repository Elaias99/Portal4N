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
<body>
    @php
        $currentPrefijo = request('prefijo', request('filter_prefijo', ''));
        $currentCodigoTracking = request('codigo_tracking', request('filter_codigo_tracking', ''));
        $currentDestino = request('destino', request('filter_destino', ''));
        $currentFechaProceso = request('fecha_proceso', request('filter_fecha_proceso', ''));
    @endphp

    <div class="container py-4">
        <div class="mb-4">
            <h1 class="mb-1">LATAM Tracking</h1>
            <p class="text-muted mb-0">
                Consulta interna de códigos almacenados y estado LATAM.
            </p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Revisa estos campos:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <h2 class="h5 mb-0">Filtrar registros</h2>

                    @if($currentFechaProceso)
                        <span class="badge text-bg-light border">
                            Mostrando fecha proceso: {{ $currentFechaProceso }}
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
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('latam.tracking.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                    </div>

                    <div class="form-text mt-2">
                        Si no indicas filtros, el sistema trabaja con la fecha del día.
                    </div>
                </form>
            </div>
        </div>

        @if(isset($rows) && count($rows) > 0)
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <h2 class="h5 mb-0">Trackings almacenados</h2>
                        <span class="badge text-bg-light border">
                            {{ count($rows) }} registro(s)
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Prefijo</th>
                                    <th>Código</th>
                                    <th>Destino</th>
                                    <th>Fecha proceso</th>
                                    <th style="width: 220px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $row['prefix'] }}</td>
                                        <td>{{ $row['code'] }}</td>
                                        <td>{{ $row['destino'] }}</td>
                                        <td>{{ $row['fecha_proceso'] }}</td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <form method="POST" action="{{ route('latam.tracking.process') }}" class="d-inline js-track-form">
                                                    @csrf
                                                    <input type="hidden" name="tracking_prefijo" value="{{ $row['prefix'] }}">
                                                    <input type="hidden" name="tracking_codigo_tracking" value="{{ $row['code'] }}">
                                                    <input type="hidden" name="tracking_doc_type" value="SO">

                                                    <input type="hidden" name="filter_prefijo" value="{{ $currentPrefijo }}">
                                                    <input type="hidden" name="filter_codigo_tracking" value="{{ $currentCodigoTracking }}">
                                                    <input type="hidden" name="filter_destino" value="{{ $currentDestino }}">
                                                    <input type="hidden" name="filter_fecha_proceso" value="{{ $currentFechaProceso }}">

                                                    <button type="submit" class="btn btn-sm btn-primary js-track-submit">
                                                        Consultar estado
                                                    </button>
                                                </form>

                                                <a href="{{ $row['url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    Abrir LATAM
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning">
                No se encontraron registros con esos filtros.
            </div>
        @endif

        @if(!empty($trackingError) || data_get($trackingResult, 'ok'))
            <div id="resultado-consulta">
                <div id="latam-tracking-react"></div>

                <script type="application/json" id="latam-tracking-react-props">{!!
                    json_encode([
                        'trackingLookup' => $trackingLookup,
                        'trackingResult' => $trackingResult,
                        'trackingError' => $trackingError,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                !!}</script>
            </div>
        @endif

        <div class="mt-4">
            <a href="{{ url('/empleados') }}" class="btn btn-secondary">
                Volver a empleados
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-track-form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('.js-track-submit');
                    if (!button) return;

                    button.disabled = true;
                    button.textContent = 'Consultando...';
                });
            });

            const resultado = document.getElementById('resultado-consulta');
            if (resultado) {
                resultado.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    </script>
</body>
</html>