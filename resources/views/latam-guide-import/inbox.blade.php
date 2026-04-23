<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correos LATAM del día</title>

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
            $selectedEmailId = $selectedEmailId ?? null;
            $preview = $preview ?? null;
            $error = $error ?? null;
        @endphp

        <div class="mb-4">
            <h1 class="mb-1">Correos LATAM del día</h1>
            <p class="text-muted mb-0">
                Revisa los correos candidatos del día, previsualiza el adjunto y guarda la guía en el sistema.
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
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Resumen del día</h2>
                        <p class="text-muted mb-0">
                            Fecha: {{ $today }}
                        </p>
                    </div>

                    <span class="badge text-bg-dark">
                        {{ count($emails ?? []) }} correo(s) candidato(s)
                    </span>
                </div>

                @if (empty($emails))
                    <div class="alert alert-warning mb-0">
                        No hay correos LATAM candidatos para hoy.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Remitente</th>
                                    <th>Asunto</th>
                                    <th>Adjunto</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($emails as $email)
                                    @php
                                        $isSelected = $selectedEmailId === $email['id'];
                                    @endphp

                                    <tr class="{{ $isSelected ? 'table-active' : '' }}">
                                        <td class="fw-semibold">{{ $email['time'] }}</td>
                                        <td>{{ $email['from'] }}</td>
                                        <td>{{ $email['subject'] }}</td>
                                        <td>
                                            @if (!empty($email['attachment_name']))
                                                <span class="badge text-bg-secondary">
                                                    {{ $email['attachment_name'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">Sin adjunto</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                <a
                                                    href="{{ route('latam-guide-import.inbox.preview', $email['id']) }}"
                                                    class="btn btn-sm {{ $isSelected ? 'btn-dark' : 'btn-outline-dark' }}"
                                                >
                                                    {{ $isSelected ? 'Previsualizado' : 'Previsualizar' }}
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="{{ route('latam-guide-import.inbox.store', $email['id']) }}"
                                                    class="m-0"
                                                >
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Guardar
                                                    </button>
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

        @if (!empty($preview))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <h2 class="h5 mb-0">Previsualización detectada</h2>
                        <span class="badge text-bg-dark">Adjunto procesado</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">OS completa</div>
                                <div class="fw-semibold">{{ $preview['os'] ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">Prefijo</div>
                                <div class="fw-semibold">{{ $preview['prefijo'] ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">Código tracking</div>
                                <div class="fw-semibold">{{ $preview['codigo_tracking'] ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">Fecha emisión raw</div>
                                <div class="fw-semibold">{{ $preview['fecha_emision_raw'] ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">Origen código</div>
                                <div class="fw-semibold">{{ $preview['origen_codigo'] ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">Destino código</div>
                                <div class="fw-semibold">{{ $preview['destino_codigo'] ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">Ciudad origen</div>
                                <div class="fw-semibold">{{ $preview['ciudad_origen'] ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-white h-100">
                                <div class="text-muted small mb-1">Ciudad destino</div>
                                <div class="fw-semibold">{{ $preview['ciudad_destino'] ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Cómo quedará guardado</h2>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Prefijo</label>
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $preview['prefijo'] ?: '' }}"
                                readonly
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Código tracking</label>
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $preview['codigo_tracking'] ?: '' }}"
                                readonly
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Fecha proceso</label>
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $preview['fecha_proceso'] ?: '' }}"
                                readonly
                            >
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Destino</label>
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $preview['destino_formulario'] ?: '' }}"
                                readonly
                            >
                        </div>
                    </div>

                    <div class="form-text mt-3">
                        Esta previsualización corresponde al correo seleccionado en la bandeja.
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