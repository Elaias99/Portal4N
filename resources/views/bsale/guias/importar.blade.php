<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Guías de despacho</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 28px;
            font-family: Arial, sans-serif;
            color: #243247;
            background: #f3f5f8;
        }

        main { max-width: 1500px; margin: auto; }
        h1 { margin: 0 0 10px; font-size: 26px; }
        h2 { margin: 0; font-size: 19px; }
        p { line-height: 1.5; }
        button, input { font: inherit; }

        .panel {
            padding: 24px;
            margin-bottom: 20px;
            background: white;
            border: 1px solid #dce2e9;
            border-radius: 12px;
        }

        .texto-secundario { color: #526175; }

        .formulario-importacion {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 22px;
        }

        .campo-archivo { flex: 1; min-width: 220px; }
        label { display: block; font-weight: bold; margin-bottom: 10px; }
        input[type="file"] { width: 100%; max-width: 100%; }

        .boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 16px;
            border: 1px solid transparent;
            border-radius: 7px;
            background: #1769aa;
            color: white;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .boton:hover:not(:disabled) { background: #12558b; }
        .boton:disabled { opacity: .55; cursor: not-allowed; }
        .boton svg { width: 18px; height: 18px; flex-shrink: 0; }
        .boton-pdf { background: #18734c; }
        .boton-pdf:hover { background: #115b3b !important; }

        :focus-visible {
            outline: 3px solid #559fe0;
            outline-offset: 3px;
        }

        .resumen-importacion,
        .encabezado-grupo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 18px;
        }

        .etiquetas {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .etiqueta {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            background: #edf2f7;
            color: #435269;
            font-size: 13px;
        }

        .fecha { margin: 7px 0 0; font-size: 14px; }
        .acciones { text-align: right; }
        .acciones form { margin: 0; }

        .estado {
            display: block;
            margin-top: 9px;
            font-size: 13px;
        }

        .estado-generada { color: #18734c; }
        .estado-pendiente { color: #68778b; }

        .cabecera-grupo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            margin: 24px 0;
            padding-top: 20px;
            border-top: 1px solid #e5eaf0;
        }

        .cabecera-grupo dt {
            margin-bottom: 6px;
            color: #68778b;
            font-size: 12px;
        }

        .cabecera-grupo dd {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        summary {
            cursor: pointer;
            padding: 10px 0;
            color: #1769aa;
            font-weight: bold;
        }

        .tabla {
            overflow-x: auto;
            margin-top: 12px;
            border: 1px solid #dce2e9;
            border-radius: 7px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e5eaf0;
            text-align: left;
            vertical-align: top;
        }

        thead th { background: #edf2f7; white-space: nowrap; }
        td.detalle { min-width: 300px; white-space: pre-wrap; line-height: 1.5; }
        .numero { text-align: right; white-space: nowrap; }
        tfoot { background: #f6f8fa; font-weight: bold; }
        tfoot th, tfoot td { border-bottom: 0; }

        .aviso {
            padding: 14px 16px;
            border-radius: 7px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .aviso-error { background: #fff0f0; color: #9f2222; }
        .aviso-atencion { background: #fff7e6; color: #805800; }
        .aviso ul { margin-bottom: 0; }
        .vacio { padding: 28px 0; text-align: center; color: #68778b; }


        .campos-destino {
            display: grid;
            gap: 12px;
            margin-bottom: 14px;
            text-align: left;
            max-width: 280px;
        }

        .campos-destino label {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .campos-destino input {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid #ccd5df;
            border-radius: 6px;
        }

        @media (max-width: 640px) {
            body { padding: 12px; }
            .panel { padding: 18px; }
            .acciones { width: 100%; text-align: left; }
            .acciones .boton { width: 100%; }
            .formulario-importacion .boton { width: 100%; }
        }
    </style>

</head>

<body>




<main>
    @php
        $rutaGeneracionDisponible = \Illuminate\Support\Facades\Route::has(
            'bsale.guias.generar'
        );


        $resultados = $resultados ?? [];


    @endphp

    <section class="panel">
        <h1>Guías de despacho</h1>

        <p class="texto-secundario">
            Importa el Excel de Access y revisa cada despacho antes de generar su guía.
        </p>

        <form
            class="formulario-importacion"
            action="{{ route('bsale.guias.previsualizar') }}"
            method="POST"
            enctype="multipart/form-data"
        >
            @csrf

            <div class="campo-archivo">
                <label for="excel">Archivo Excel</label>

                <input
                    id="excel"
                    name="excel"
                    type="file"
                    accept=".xlsx"
                    required
                    aria-describedby="ayuda-excel"
                >

                <p id="ayuda-excel" class="texto-secundario">
                    Formato XLSX · Máximo 5 MB
                </p>
            </div>

            <button class="boton" type="submit">
                Cargar y revisar
            </button>






        </form>


            {{-- @if ($archivo !== null)
                <form
                    action="{{ route('bsale.guias.limpiar') }}"
                    method="POST"
                    style="margin-top: 16px;"
                >
                    @csrf

                    <button class="boton" type="submit">
                        Limpiar importación
                    </button>
                </form>
            @endif --}}



    </section>

    @if ($errors->any())
        <section class="panel aviso aviso-error" role="alert">
            <strong>Revisa lo siguiente:</strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($archivo !== null)
        <section class="panel resumen-importacion">
            <div>
                <h2>Despachos agrupados</h2>
                <p class="texto-secundario">{{ $archivo }}</p>

                <div class="etiquetas">
                    <span class="etiqueta">{{ count($filas) }} filas</span>
                    <span class="etiqueta">{{ count($grupos) }} grupos</span>
                </div>
            </div>

            <span class="etiqueta">Generación sin declaración al SII</span>
        </section>

        @foreach ($grupos as $grupo)
            @php
                $cabecera = $grupo['cabecera'];
                $identificador = $grupo['identificador'] ?? null;

                $resultado = $identificador
                    ? ($resultados[$identificador] ?? [])
                    : [];

                $estado = $resultado['estado'] ?? 'pendiente';
                $generada = $estado === 'generada';

                $bloqueada = in_array(
                    $estado,
                    ['enviando', 'incierta'],
                    true
                );

                $puedeGenerar = $rutaGeneracionDisponible
                    && $identificador
                    && ! $generada
                    && ! $bloqueada;

                $pdf = $resultado['pdf'] ?? null;

                // Admitir únicamente enlaces HTTPS para abrir el documento.
                $pdfValido = is_string($pdf)
                    && filter_var($pdf, FILTER_VALIDATE_URL)
                    && strtolower(parse_url($pdf, PHP_URL_SCHEME) ?? '') === 'https';
            @endphp

            <article class="panel" id="grupo-{{ $identificador ?? $loop->index }}">
                <header class="encabezado-grupo">
                    <div>
                        <h2>{{ $cabecera['Posta'] ?: 'Sin posta' }}</h2>

                        <p class="fecha texto-secundario">
                            Fecha de transporte:
                            {{ $cabecera['FechaGuiaTransporte'] ?: 'Sin fecha' }}
                        </p>

                        <div class="etiquetas">
                            <span class="etiqueta">
                                {{ count($grupo['filas']) }} detalles
                            </span>

                            <span class="etiqueta">
                                Bultos:
                                {{ $grupo['totales']['Bultos'] ?? 'Revisar datos' }}
                            </span>

                            <span class="etiqueta">
                                Kilos:
                                {{ $grupo['totales']['PesoTotal'] ?? 'Revisar datos' }}
                            </span>
                        </div>
                    </div>

                    <div class="acciones">
                        @if ($generada)
                            @if ($pdfValido)
                                <a
                                    class="boton boton-pdf"
                                    href="{{ $pdf }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Ver PDF
                                </a>
                            @endif

                            <span class="estado estado-generada">
                                Guía generada
                                @if (! empty($resultado['numero']))
                                    · N.º {{ $resultado['numero'] }}
                                @endif
                            </span>

                            @unless ($pdfValido)
                                <span class="estado">
                                    Bsale no devolvió un enlace PDF disponible.
                                </span>
                            @endunless
                        @elseif ($bloqueada)
                            <button class="boton" type="button" disabled>
                                Pendiente de comprobación
                            </button>
                        @elseif ($puedeGenerar)



                            <form
                                class="formulario-generacion"
                                action="{{ route('bsale.guias.generar', [
                                    'identificador' => $identificador,
                                ]) }}"
                                method="POST"
                            >
                                @csrf


                                    @php
                                        $destinoInicial = trim($cabecera['Posta'] ?? '');
                                    @endphp

                                    <div class="campos-destino">
                                        <div>
                                            <label for="comuna-{{ $identificador }}">
                                                Comuna de destino
                                            </label>

                                            <input
                                                id="comuna-{{ $identificador }}"
                                                name="municipality"
                                                type="text"
                                                maxlength="100"
                                                value="{{ $grupo['comuna_destino'] ?? $destinoInicial }}"
                                                required
                                            >
                                        </div>

                                        <div>
                                            <label for="ciudad-{{ $identificador }}">
                                                Ciudad de destino
                                            </label>

                                            <input
                                                id="ciudad-{{ $identificador }}"
                                                name="city"
                                                type="text"
                                                maxlength="100"
                                                value="{{ $grupo['ciudad_destino'] ?? $destinoInicial }}"
                                                required
                                            >
                                        </div>
                                    </div>


                                <button class="boton" type="submit">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true"
                                    >
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <path d="M14 2v6h6M8 13h8M8 17h5"/>
                                    </svg>

                                    <span>Generar guía</span>
                                </button>





                            </form>






                        @else
                            <button class="boton" type="button" disabled>
                                Generar guía
                            </button>

                            <span class="estado estado-pendiente">
                                Conexión del botón pendiente
                            </span>
                        @endif
                    </div>
                </header>

                @if (! empty($resultado['mensaje']))
                    <p
                        class="aviso {{ $bloqueada ? 'aviso-atencion' : 'aviso-error' }}"
                        role="status"
                    >
                        {{ $resultado['mensaje'] }}
                    </p>
                @endif

                <dl class="cabecera-grupo">
                    @foreach ([
                        'Troncal' => 'Troncal',
                        'DestinoCarga' => 'Destino de carga',
                        'DireccionOrigen' => 'Dirección de origen',
                        'DireccionDestino' => 'Dirección de destino',
                        'Patente' => 'Patente',
                        'Chofer' => 'Chofer',
                        'RutChofer' => 'RUT del chofer',
                    ] as $campo => $etiqueta)
                        <div>
                            <dt>{{ $etiqueta }}</dt>
                            <dd>{{ $cabecera[$campo] ?: 'Sin dato' }}</dd>
                        </div>
                    @endforeach
                </dl>

                <details @if ($loop->first) open @endif>
                    <summary>
                        Detalle de la carga · {{ count($grupo['filas']) }} registros
                    </summary>

                    <div
                        class="tabla"
                        tabindex="0"
                        aria-label="Carga de {{ $cabecera['Posta'] }}"
                    >
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Fila Excel</th>
                                    <th scope="col">Detalle</th>
                                    <th scope="col" class="numero">Bultos</th>
                                    <th scope="col" class="numero">Peso (kg)</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($grupo['filas'] as $fila)
                                    <tr>
                                        <td>{{ $fila['numero_excel'] }}</td>
                                        <td class="detalle">{{ $fila['datos']['Detalle'] }}</td>
                                        <td class="numero">{{ $fila['datos']['Bultos'] }}</td>
                                        <td class="numero">{{ $fila['datos']['PesoTotal'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th scope="row" colspan="2">Total del grupo</th>

                                    <td class="numero">
                                        {{ $grupo['totales']['Bultos'] ?? 'Revisar datos' }}
                                    </td>

                                    <td class="numero">
                                        {{ $grupo['totales']['PesoTotal'] ?? 'Revisar datos' }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </details>
            </article>
        @endforeach
    @else
        <section class="panel vacio">
            Carga un archivo Excel para visualizar los despachos.
        </section>
    @endif




    @if (isset($historial) && $historial->hasPages())
        <nav class="panel" aria-label="Páginas del historial">
            @if ($historial->previousPageUrl())
                <a class="boton" href="{{ $historial->previousPageUrl() }}">
                    Anterior
                </a>
            @endif

            <span style="margin: 0 15px;">
                Página {{ $historial->currentPage() }}
                de {{ $historial->lastPage() }}
            </span>

            @if ($historial->nextPageUrl())
                <a class="boton" href="{{ $historial->nextPageUrl() }}">
                    Siguiente
                </a>
            @endif
        </nav>
    @endif






</main>






<script>
    document.querySelectorAll('.formulario-generacion').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (formulario.dataset.enviando === '1') {
                evento.preventDefault();
                return;
            }

            formulario.dataset.enviando = '1';

            const boton = formulario.querySelector('button[type="submit"]');

            boton.disabled = true;
            boton.querySelector('span').textContent = 'Generando…';
            boton.setAttribute('aria-busy', 'true');
        });
    });

    // Restaurar el botón si se vuelve mediante el historial del navegador.
    window.addEventListener('pageshow', (evento) => {
        if (evento.persisted) {
            window.location.reload();
        }
    });
</script>
</body>
</html>