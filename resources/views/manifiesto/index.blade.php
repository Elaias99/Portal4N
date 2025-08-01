<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Manifiestos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4">Subir o Pegar Manifiesto</h1>
            <p class="lead text-muted">Procesa los manifiestos de SAC, Ecommerce y Mayorista de forma eficiente.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if(session('manifiestos_acumulados'))
            <div class="mb-4 d-flex justify-content-center">
                <form action="{{ route('manifiestos.export') }}" method="GET" class="mr-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        Descargar Excel
                    </button>
                </form>

                <form action="{{ route('manifiestos.limpiar') }}" method="GET">
                    <button type="submit" class="btn btn-danger btn-lg">
                        Limpiar registros
                    </button>
                </form>
            </div>
        @endif

        @if(isset($filasSinArea) && count($filasSinArea) > 0)
            <div class="card border-warning mb-4">
                <div class="card-body">
                    <h4 class="card-title text-warning">🟡 Registros sin área detectada</h4>
                    <p class="card-text">Por favor selecciona el área correspondiente:</p>

                    <form action="{{ route('manifiesto.confirmar-area') }}" method="POST">
                        @csrf
                        <input type="hidden" name="filas" value="{{ json_encode($filasSinArea) }}">

                        <div class="form-group">
                            <label for="fecha_confirmar">Fecha del manifiesto:</label>
                            <input type="date" name="fecha_confirmar" id="fecha_confirmar" required class="form-control"
                                   value="{{ old('fecha_confirmar', $fechaSinArea ?? '') }}">
                        </div>

                        <div class="form-group">
                            <label for="area">Área:</label>
                            <select name="area" class="form-control" required>
                                <option value="">-- Selecciona un área --</option>
                                <option value="SAC">SAC</option>
                                <option value="Ecommerce">Ecommerce</option>
                                <option value="Mayorista">Mayorista</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">✅ Confirmar área</button>
                    </form>
                </div>
            </div>
        @endif

        @if(!isset($filasSinArea) || count($filasSinArea) === 0)
            <div class="card mb-5">
                <div class="card-body">
                    <h4 class="card-title">Pegar manifiesto desde el correo</h4>
                    <form action="{{ route('manifiesto.paste') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="fecha_individual">Fecha del manifiesto:</label>
                            <input type="date" name="fecha_individual" id="fecha_individual" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="tabla">Tabla copiada desde el correo (Outlook):</label>
                            <textarea name="tabla" id="tabla" rows="10" class="form-control" placeholder="Copia la tabla del correo y pégala aquí..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-info btn-block">Analizar</button>
                    </form>
                </div>
            </div>
        @endif

        @if(!empty($codigosDuplicados))
            <div class="alert alert-warning">
                <h5>⚠️ Códigos duplicados detectados:</h5>
                <table class="table table-bordered table-sm mt-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Código</th>
                            <th>Veces repetido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($codigosDuplicados as $codigo => $veces)
                            <tr>
                                <td>{{ $codigo }}</td>
                                <td>{{ $veces }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($headers) && isset($rows))
            <h4 class="mb-3">Vista previa de los datos</h4>


            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-bordered table-sm mb-0">
                    <thead class="thead-dark">
                        <tr>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                @for ($i = 0; $i < count($headers); $i++)
                                    @php $cell = $row[$i] ?? ''; @endphp
                                    <td>{{ $cell }}</td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>





        @endif
    </div>
</body>
</html>
