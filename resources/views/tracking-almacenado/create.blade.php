<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Tracking Almacenado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f7f7f7;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            background: #2d5be3;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.95;
        }

        button:disabled {
            background: #9aa4b2;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn-secondary {
            background: #198754;
        }

        .btn-danger {
            background: #dc3545;
        }

        .alert-success {
            background: #e7f7ec;
            color: #1f6b3b;
            border: 1px solid #b7e4c7;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-errors {
            background: #ffe5e5;
            border: 1px solid #ffb3b3;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        ul {
            margin: 10px 0 0 20px;
        }

        .tracking-group {
            border: 1px solid #e5e5e5;
            padding: 15px;
            border-radius: 6px;
            background: #fafafa;
        }

        .tracking-item {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .tracking-input-wrapper {
            flex: 1;
        }

        .tracking-actions {
            margin-top: 10px;
        }

        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
        }

        .submit-area {
            margin-top: 20px;
        }

        .tracking-error {
            display: none;
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }

        .input-invalid {
            border: 1px solid #dc3545 !important;
        }

        .duplicate-warning {
            display: none;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffe08a;
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registrar Tracking Almacenado</h1>

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-errors">
                <strong>Revisa estos campos:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tracking-almacenado.store') }}" method="POST" id="tracking-form">
            @csrf

            <div class="field">
                <label for="prefijo">Prefijo</label>
                <input
                    type="text"
                    id="prefijo"
                    name="prefijo"
                    value="{{ old('prefijo', '972') }}"
                    maxlength="3"
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

            <div class="field" id="campo-destino-otro" style="display: none;">
                <label for="destino_otro">Especifique destino</label>
                <input
                    type="text"
                    id="destino_otro"
                    name="destino_otro"
                    value="{{ old('destino_otro') }}"
                    maxlength="100"
                >
            </div>

            <div class="field">
                <label for="fecha_proceso">Fecha proceso</label>
                <input
                    type="date"
                    id="fecha_proceso"
                    name="fecha_proceso"
                    value="{{ old('fecha_proceso', date('Y-m-d')) }}"
                >
            </div>

            <div class="field">
                <label>Códigos tracking</label>

                <div class="tracking-group">
                    <div id="duplicate-warning" class="duplicate-warning">
                        Hay códigos tracking repetidos. Corrígelos antes de guardar.
                    </div>

                    <div id="tracking-container">
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

                                <button type="button" class="btn-danger btn-remove">Quitar</button>
                            </div>
                        @endforeach
                    </div>

                    <div class="tracking-actions">
                        <button type="button" id="btn-agregar-codigo" class="btn-secondary">+ Agregar código</button>
                    </div>

                    <div class="help-text">
                        Usa el botón para agregar todos los códigos que compartan el mismo prefijo, destino y fecha.
                    </div>
                </div>
            </div>

            <div class="submit-area">
                <button type="submit" id="btn-guardar" disabled>Guardar</button>
            </div>
        </form>
    </div>

    <script>
        function toggleDestinoOtro() {
            const destino = document.getElementById('destino');
            const campoOtro = document.getElementById('campo-destino-otro');

            if (destino.value === 'OTRO') {
                campoOtro.style.display = 'block';
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

                <button type="button" class="btn-danger btn-remove">Quitar</button>
            `;

            return div;
        }

        function actualizarBotonesQuitar() {
            const items = document.querySelectorAll('.tracking-item');
            const botones = document.querySelectorAll('.btn-remove');

            botones.forEach(function (boton) {
                boton.style.display = items.length > 1 ? 'inline-block' : 'none';
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
                if (event.target.classList.contains('btn-remove')) {
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