<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outlook LATAM</title>

    @viteReactRefresh
    @vite([
        'resources/css/app.css',
        'resources/css/appcustom.css',
        'resources/sass/app.scss',
        'resources/js/app.js',
    ])
</head>
<body class="bg-light">
    <div class="container py-4 py-lg-5">
        @php
            $emails = $emails ?? [];
            $preview = $preview ?? null;
            $error = $error ?? null;
            $isConnected = $isConnected ?? false;
            $selectedMessageId = $selectedMessageId ?? null;
            $connectedUser = $connectedUser ?? null;
            $filters = $filters ?? [];

            $previewMainFields = [
                'OS completa' => $preview['os'] ?? null,
                'Prefijo' => $preview['prefijo'] ?? null,
                'Código tracking' => $preview['codigo_tracking'] ?? null,
                'Fecha proceso' => $preview['fecha_proceso'] ?? null,
                'Origen código' => $preview['origen_codigo'] ?? null,
                'Destino código' => $preview['destino_codigo'] ?? null,
                'Ciudad origen' => $preview['ciudad_origen'] ?? null,
            ];

            // $previewExtraFields = [
            //     'Ciudad destino' => $preview['ciudad_destino'] ?? null,
            //     'Destino formulario' => $preview['destino_formulario'] ?? null,
            // ];
        @endphp

        <div class="mb-4">
            <h1 class="mb-1">Conexión Outlook LATAM</h1>
            <p class="text-muted mb-0">
                Desde aquí puedes comprobar si la conexión con Outlook fue exitosa, revisar correos candidatos y previsualizar la guía antes de guardarla.
            </p>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning border-0 shadow-sm">
                {{ session('warning') }}
            </div>
        @endif

        @if (!empty($error))
            <div class="alert alert-danger border-0 shadow-sm">
                {{ $error }}
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8">
                        <h2 class="h5 mb-2">Estado de conexión</h2>

                        @if ($isConnected)
                            <div class="mb-2">
                                <span class="badge text-bg-success">Conectado correctamente</span>
                            </div>
                        @else
                            <div class="mb-2">
                                <span class="badge text-bg-secondary">Sin conexión</span>
                            </div>
                        @endif

                        @if ($isConnected && !empty($connectedUser))
                            <div class="small text-muted mb-2">
                                Conectado como:
                                <strong>{{ $connectedUser['mail'] ?? $connectedUser['userPrincipalName'] ?? 'usuario desconocido' }}</strong>
                                @if (!empty($connectedUser['displayName']))
                                    ({{ $connectedUser['displayName'] }})
                                @endif
                            </div>
                        @endif

                        <p class="text-muted mb-0">
                            Verifica si Outlook ya está conectado correctamente antes de consultar los correos LATAM.
                        </p>
                    </div>

                    <div class="col-lg-4">
                        <div class="d-flex justify-content-lg-end gap-2 flex-wrap">
                            @if (!$isConnected)
                                <a href="{{ route('outlook-mails.connect') }}" class="btn btn-primary">
                                    Conectar Outlook
                                </a>
                            @else
                                <form method="POST" action="{{ route('outlook-mails.disconnect') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">
                                        Desconectar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($isConnected)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Correos candidatos</h2>
                            <p class="text-muted mb-0">
                                Se muestran correos con adjuntos PDF del remitente LATAM configurado.
                            </p>
                        </div>

                        <span class="badge text-bg-dark">
                            {{ count($emails) }} correo(s)
                        </span>
                    </div>

                    <form method="GET" action="{{ route('outlook-mails.index') }}" class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Fecha desde</label>
                            <input
                                type="date"
                                name="fecha_desde"
                                value="{{ $filters['fecha_desde'] ?? '' }}"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label mb-1">Fecha hasta</label>
                            <input
                                type="date"
                                name="fecha_hasta"
                                value="{{ $filters['fecha_hasta'] ?? '' }}"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                Filtrar
                            </button>

                            <a href="{{ route('outlook-mails.index') }}" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>
                    </form>



                    @if (empty($emails))
                        <div class="alert alert-secondary mb-0">
                            No hay correos candidatos o todavía no se han cargado.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="min-width: 220px;">Remitente</th>
                                        <th>Asunto</th>
                                        <th style="min-width: 160px;">Fecha</th>
                                        <th class="text-end" style="min-width: 180px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($emails as $email)
                                        @php
                                            $isSelected = $selectedMessageId === ($email['id'] ?? null);
                                            $receivedAt = !empty($email['received_at'])
                                                ? date('d-m-Y H:i', strtotime($email['received_at']))
                                                : '-';
                                        @endphp

                                        <tr class="{{ $isSelected ? 'table-active' : '' }}">
                                            <td class="text-break">{{ $email['from'] ?? '-' }}</td>
                                            <td>{{ $email['subject'] ?? '-' }}</td>
                                            <td>{{ $receivedAt }}</td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                    <a
                                                        href="{{ route('outlook-mails.preview', $email['id']) }}?{{ http_build_query([
                                                            'fecha_desde' => $filters['fecha_desde'] ?? null,
                                                            'fecha_hasta' => $filters['fecha_hasta'] ?? null,
                                                        ]) }}"
                                                        class="btn btn-sm {{ $isSelected ? 'btn-dark' : 'btn-outline-dark' }}"
                                                    >
                                                        {{ $isSelected ? 'Previsualizado' : 'Previsualizar' }}
                                                    </a>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('outlook-mails.store', $email['id']) }}"
                                                        class="m-0"
                                                    >
                                                        @csrf

                                                        @if ($selectedMessageId === ($email['id'] ?? null) && !empty($preview))
                                                            <input type="hidden" name="prefijo" value="{{ $preview['prefijo'] ?? '' }}">
                                                            <input type="hidden" name="codigo_tracking" value="{{ $preview['codigo_tracking'] ?? '' }}">
                                                            <input type="hidden" name="fecha_proceso" value="{{ $preview['fecha_proceso'] ?? '' }}">
                                                            <input type="hidden" name="destino" value="{{ $preview['destino_formulario'] ?? '' }}">

                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                Guardar
                                                            </button>
                                                        @else
                                                            <button type="button" class="btn btn-sm btn-success" disabled>
                                                                Guardar
                                                            </button>
                                                        @endif
                                                    </form>


                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if (!empty($preview))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Previsualización del PDF</h2>
                            <p class="text-muted mb-0">
                                Revisa cómo quedaron interpretados los datos antes de guardar el tracking.
                            </p>
                        </div>

                        <span class="badge text-bg-dark">Adjunto procesado</span>
                    </div>

                    <div class="row g-3">
                        @foreach ($previewMainFields as $label => $value)
                            <div class="col-md-6 col-xl-3">
                                <div class="border rounded p-3 bg-white h-100">
                                    <div class="text-muted small mb-1">{{ $label }}</div>
                                    <div class="fw-semibold text-break">{{ filled($value) ? $value : '-' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-4">
            <a href="{{ url('/empleados') }}" class="btn btn-outline-secondary">
                Volver a empleados
            </a>
        </div>
    </div>
</body>
</html>