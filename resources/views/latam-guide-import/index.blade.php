<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar guía LATAM</title>

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
        <div class="mb-4">
            <h1 class="mb-1">Importar guía LATAM</h1>
            <p class="text-muted mb-0">
                Sube un PDF para extraer la información y guardar el tracking en el sistema.
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

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <strong>Revisa estos campos:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $validationError)
                        <li>{{ $validationError }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($error))
            <div class="alert alert-danger border-0 shadow-sm">
                {{ $error }}
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Subir PDF LATAM</h2>

                <form
                    method="POST"
                    action="{{ route('latam-guide-import.preview-pdf') }}"
                    enctype="multipart/form-data"
                >
                    @csrf

                    <div class="mb-3">
                        <label for="pdf" class="form-label">Archivo PDF</label>
                        <input
                            type="file"
                            name="pdf"
                            id="pdf"
                            class="form-control"
                            accept="application/pdf"
                            required
                        >
                        <div class="form-text">
                            Sube un PDF de la guía LATAM para previsualizar los datos detectados.
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-dark">
                            Previsualizar PDF
                        </button>

                        <a href="{{ route('latam-guide-import.index') }}" class="btn btn-outline-secondary">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @if (!empty($preview))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <h2 class="h5 mb-0">Previsualización detectada</h2>
                        <span class="badge text-bg-dark">Lista para guardar</span>
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
                    <h2 class="h5 mb-3">Cómo quedaría en tu formulario</h2>

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
                            <label class="form-label">Destino sugerido para formulario</label>
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $preview['destino_formulario'] ?: '' }}"
                                readonly
                            >
                        </div>
                    </div>

                    <div class="form-text mt-3">
                        Si los datos son correctos, ya puedes guardarlos en la tabla <code>trackings_almacenados</code>.
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h2 class="h5 mb-1">Guardar en el sistema</h2>
                            <p class="text-muted mb-0">
                                Se registrará el tracking con los valores detectados desde el PDF.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('latam-guide-import.store-preview') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="prefijo" value="{{ $preview['prefijo'] ?? '' }}">
                            <input type="hidden" name="codigo_tracking" value="{{ $preview['codigo_tracking'] ?? '' }}">
                            <input type="hidden" name="fecha_proceso" value="{{ $preview['fecha_proceso'] ?? '' }}">
                            <input type="hidden" name="destino" value="{{ $preview['destino_formulario'] ?? '' }}">

                            <button type="submit" class="btn btn-success">
                                Guardar en el sistema
                            </button>
                        </form>
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