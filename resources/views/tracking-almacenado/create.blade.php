<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Tracking Almacenado</title>
    <style>
        :root {
            --bg: #f4f6f8;
            --panel: #ffffff;
            --panel-soft: #f8fafc;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --text: #0f172a;
            --muted: #475569;
            --muted-soft: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --neutral-btn: #334155;
            --neutral-btn-hover: #1e293b;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --warning-soft: #fff7ed;
            --warning-border: #fdba74;
            --success-soft: #f0fdf4;
            --success-border: #86efac;
            --shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            --shadow-soft: 0 8px 20px rgba(15, 23, 42, 0.05);
            --radius-xl: 20px;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 10px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.05), transparent 28%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
        }

        .page {
            max-width: 980px;
            margin: 0 auto;
        }

        .shell {
            background: var(--panel);
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            padding: 28px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .eyebrow {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--primary);
        }

        h1 {
            margin: 0;
            font-size: 2.1rem;
            line-height: 1.1;
            color: var(--text);
        }

        .page-description {
            margin: 12px 0 0;
            max-width: 760px;
            color: var(--muted);
            line-height: 1.7;
            font-size: 1rem;
        }

        .alert {
            border-radius: var(--radius-md);
            padding: 14px 16px;
            margin-bottom: 18px;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .alert strong {
            display: block;
            margin-bottom: 4px;
        }

        .alert-success {
            background: var(--success-soft);
            border: 1px solid var(--success-border);
            color: #166534;
        }

        .alert-errors {
            background: var(--danger-soft);
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .alert-errors ul {
            margin: 8px 0 0 18px;
            padding: 0;
        }

        .card {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            padding: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
        }

        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            background: #fff;
            padding: 0 14px;
            font-size: 0.95rem;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        select {
            appearance: none;
            background-image:
                linear-gradient(45deg, transparent 50%, #64748b 50%),
                linear-gradient(135deg, #64748b 50%, transparent 50%);
            background-position:
                calc(100% - 20px) calc(50% - 3px),
                calc(100% - 14px) calc(50% - 3px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding-right: 42px;
        }

        input::placeholder {
            color: #94a3b8;
        }

        input:focus,
        select:focus {
            border-color: rgba(37, 99, 235, 0.55);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .tracking-section {
            margin-top: 22px;
        }

        .tracking-label {
            margin-bottom: 8px;
        }

        .tracking-group {
            border: 1px solid var(--border);
            background: var(--panel-soft);
            border-radius: var(--radius-lg);
            padding: 18px;
        }

        .duplicate-warning {
            display: none;
            margin-bottom: 14px;
            border: 1px solid var(--warning-border);
            background: var(--warning-soft);
            color: #9a3412;
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .tracking-list {
            display: grid;
            gap: 12px;
        }

        .tracking-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: start;
        }

        .tracking-input-wrapper {
            min-width: 0;
        }

        .tracking-error {
            display: none;
            margin-top: 6px;
            color: #b91c1c;
            font-size: 0.82rem;
        }

        .input-invalid {
            border-color: rgba(220, 38, 38, 0.7) !important;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
        }

        .tracking-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .help-text {
            margin-top: 12px;
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--muted-soft);
        }

        .submit-area {
            margin-top: 24px;
            display: flex;
            justify-content: flex-start;
        }

        button,
        .btn-inline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 700;
            transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        button:hover,
        .btn-inline:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-neutral {
            background: var(--neutral-btn);
            color: #fff;
        }

        .btn-neutral:hover {
            background: var(--neutral-btn-hover);
        }

        .btn-remove {
            background: #fff;
            color: #334155;
            border: 1px solid var(--border-strong);
            min-width: 92px;
        }

        .btn-remove:hover {
            background: #f8fafc;
        }

        button:disabled {
            background: #94a3b8 !important;
            color: #e2e8f0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            body {
                padding: 14px;
            }

            .shell {
                padding: 18px;
                border-radius: 18px;
            }

            h1 {
                font-size: 1.7rem;
            }

            .page-description {
                font-size: 0.95rem;
            }

            .card {
                padding: 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .tracking-item {
                grid-template-columns: 1fr;
            }

            .btn-remove,
            .btn-neutral,
            .btn-primary {
                width: 100%;
            }

            .tracking-actions,
            .submit-area {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <form method="POST" action="{{ route('logout') }}" style="display:flex; justify-content:flex-end; margin-bottom:16px;">
            @csrf
            <button type="submit" class="btn-remove">Cerrar sesión</button>
        </form>
        <div class="shell">
            <div class="page-header">
                <p class="eyebrow">Registro interno</p>
                <h1>Registrar Tracking Almacenado</h1>
                <p class="page-description">
                    Ingresa varios códigos tracking que compartan el mismo prefijo, destino y fecha de proceso.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-errors">
                    <strong>Revisa estos campos:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <form action="{{ route('tracking-almacenado.store') }}" method="POST" id="tracking-form">
                    @csrf

                    <div class="form-grid">
                        <div class="field">
                            <label for="prefijo">Prefijo</label>
                            <input
                                type="text"
                                id="prefijo"
                                name="prefijo"
                                value="{{ old('prefijo', '972') }}"
                                maxlength="3"
                                placeholder="Ej: 972"
                            >
                        </div>

                        <div class="field">
                            <label for="destino">Destino</label>
                            <select id="destino" name="destino">
                                <option value="">Seleccione un destino</option>
                                @foreach($destinos as $destino)
                                    <option value="{{ $destino }}" {{ old('destino') === $destino ? 'selected' : '' }}>
                                        {{ $destino }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field field-full" id="campo-destino-otro" style="display: none;">
                            <label for="destino_otro">Especifique destino</label>
                            <input
                                type="text"
                                id="destino_otro"
                                name="destino_otro"
                                value="{{ old('destino_otro') }}"
                                maxlength="100"
                                placeholder="Escribe el destino manual"
                            >
                        </div>

                        <div class="field field-full">
                            <label for="fecha_proceso">Fecha proceso</label>
                            <input
                                type="date"
                                id="fecha_proceso"
                                name="fecha_proceso"
                                value="{{ old('fecha_proceso', date('Y-m-d')) }}"
                            >
                        </div>
                    </div>

                    <div class="tracking-section">
                        <label class="tracking-label">Códigos tracking</label>

                        <div class="tracking-group">
                            <div id="duplicate-warning" class="duplicate-warning">
                                Hay códigos tracking repetidos. Corrígelos antes de guardar.
                            </div>

                            <div id="tracking-container" class="tracking-list">
                                @php
                                    $codigosTracking = old('codigo_tracking', ['']);
                                    if (!is_array($codigosTracking) || count($codigosTracking) === 0) {
                                        $codigosTracking = [''];
                                    }
                                @endphp

                                @foreach($codigosTracking as $codigo)
                                    <div class="tracking-item">
                                        <div class="tracking-input-wrapper">
                                            <input
                                                type="text"
                                                name="codigo_tracking[]"
                                                value="{{ $codigo }}"
                                                maxlength="8"
                                                placeholder="Ej: 03574362"
                                                class="tracking-input"
                                            >
                                            <div class="tracking-error">
                                                Este código ya fue escrito.
                                            </div>
                                        </div>

                                        <button type="button" class="btn-remove btn-remove-action">Quitar</button>
                                    </div>
                                @endforeach
                            </div>

                            <div class="tracking-actions">
                                <button type="button" id="btn-agregar-codigo" class="btn-neutral">
                                    Agregar código
                                </button>
                            </div>

                            <div class="help-text">
                                Usa este bloque para cargar todos los códigos que compartan el mismo prefijo, destino y fecha.
                            </div>
                        </div>
                    </div>

                    <div class="submit-area">
                        <button type="submit" id="btn-guardar" class="btn-primary" disabled>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleDestinoOtro() {
            const destino = document.getElementById('destino');
            const campoOtro = document.getElementById('campo-destino-otro');

            if (destino.value === 'OTRO') {
                campoOtro.style.display = 'flex';
            } else {
                campoOtro.style.display = 'none';
            }
        }

        function crearFilaTracking(valor = '') {
            const div = document.createElement('div');
            div.className = 'tracking-item';

            div.innerHTML = `
                <div class="tracking-input-wrapper">
                    <input
                        type="text"
                        name="codigo_tracking[]"
                        value="${valor}"
                        maxlength="8"
                        placeholder="Ej: 03574362"
                        class="tracking-input"
                    >
                    <div class="tracking-error">
                        Este código ya fue escrito.
                    </div>
                </div>

                <button type="button" class="btn-remove btn-remove-action">Quitar</button>
            `;

            return div;
        }

        function actualizarBotonesQuitar() {
            const items = document.querySelectorAll('.tracking-item');
            const botones = document.querySelectorAll('.btn-remove-action');

            botones.forEach(function (boton) {
                boton.style.display = items.length > 1 ? 'inline-flex' : 'none';
            });
        }

        function normalizarCodigo(valor) {
            return valor.trim();
        }

        function validarCodigosDuplicados() {
            const inputs = document.querySelectorAll('.tracking-input');
            const warning = document.getElementById('duplicate-warning');
            const contador = {};
            let hayDuplicados = false;

            inputs.forEach(function (input) {
                const valor = normalizarCodigo(input.value);
                const error = input.parentElement.querySelector('.tracking-error');

                input.classList.remove('input-invalid');
                error.style.display = 'none';

                if (valor !== '') {
                    contador[valor] = (contador[valor] || 0) + 1;
                }
            });

            inputs.forEach(function (input) {
                const valor = normalizarCodigo(input.value);
                const error = input.parentElement.querySelector('.tracking-error');

                if (valor !== '' && contador[valor] > 1) {
                    input.classList.add('input-invalid');
                    error.style.display = 'block';
                    hayDuplicados = true;
                }
            });

            warning.style.display = hayDuplicados ? 'block' : 'none';

            return hayDuplicados;
        }

        function actualizarEstadoBotonGuardar() {
            const inputs = document.querySelectorAll('.tracking-input');
            const btnGuardar = document.getElementById('btn-guardar');

            const hayAlMenosUnCodigo = Array.from(inputs).some(function (input) {
                return input.value.trim() !== '';
            });

            const hayDuplicados = validarCodigosDuplicados();

            btnGuardar.disabled = !hayAlMenosUnCodigo || hayDuplicados;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const destino = document.getElementById('destino');
            const trackingContainer = document.getElementById('tracking-container');
            const btnAgregarCodigo = document.getElementById('btn-agregar-codigo');
            const form = document.getElementById('tracking-form');

            if (destino) {
                destino.addEventListener('change', toggleDestinoOtro);
                toggleDestinoOtro();
            }

            btnAgregarCodigo.addEventListener('click', function () {
                const nuevaFila = crearFilaTracking();
                trackingContainer.appendChild(nuevaFila);
                actualizarBotonesQuitar();
                actualizarEstadoBotonGuardar();
            });

            trackingContainer.addEventListener('click', function (event) {
                if (event.target.classList.contains('btn-remove-action')) {
                    const items = document.querySelectorAll('.tracking-item');

                    if (items.length > 1) {
                        event.target.closest('.tracking-item').remove();
                        actualizarBotonesQuitar();
                        actualizarEstadoBotonGuardar();
                    }
                }
            });

            trackingContainer.addEventListener('input', function (event) {
                if (event.target.classList.contains('tracking-input')) {
                    actualizarEstadoBotonGuardar();
                }
            });

            form.addEventListener('submit', function (event) {
                const hayDuplicados = validarCodigosDuplicados();

                if (hayDuplicados) {
                    event.preventDefault();

                    const primerDuplicado = document.querySelector('.tracking-input.input-invalid');
                    if (primerDuplicado) {
                        primerDuplicado.focus();
                    }
                }
            });

            actualizarBotonesQuitar();
            actualizarEstadoBotonGuardar();
        });
    </script>
</body>
</html>