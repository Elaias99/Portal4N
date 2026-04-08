<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATAM Tracking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f7f7f7;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
        }

        .top-actions {
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            background: #2d5be3;
            border: none;
            cursor: pointer;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .filters {
            margin-top: 20px;
            margin-bottom: 25px;
            padding: 18px;
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
        }

        .filters h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .field input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .filter-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .results {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            background: #f5f5f5;
        }

        .empty {
            margin-top: 20px;
            padding: 15px;
            background: #fff8e1;
            border: 1px solid #ffe08a;
            border-radius: 4px;
        }

        .tracking-link {
            color: #2d5be3;
            text-decoration: none;
            font-weight: bold;
        }

        .tracking-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>LATAM Tracking</h1>
        <p>Aquí se muestran los códigos almacenados para consulta y seguimiento.</p>

        <div class="filters">
            <h2>Filtrar registros</h2>

            <form method="GET" action="{{ route('latam.tracking.index') }}">
                <div class="filters-grid">
                    <div class="field">
                        <label for="prefijo">Prefijo</label>
                        <input
                            type="text"
                            id="prefijo"
                            name="prefijo"
                            value="{{ request('prefijo') }}"
                            placeholder="Ej: 972"
                        >
                    </div>

                    <div class="field">
                        <label for="codigo_tracking">Código tracking</label>
                        <input
                            type="text"
                            id="codigo_tracking"
                            name="codigo_tracking"
                            value="{{ request('codigo_tracking') }}"
                            placeholder="Ej: 03574362"
                        >
                    </div>

                    <div class="field">
                        <label for="destino">Destino</label>
                        <input
                            type="text"
                            id="destino"
                            name="destino"
                            value="{{ request('destino') }}"
                            placeholder="Ej: SCL ARICA"
                        >
                    </div>

                    <div class="field">
                        <label for="fecha_proceso">Fecha proceso</label>
                        <input
                            type="date"
                            id="fecha_proceso"
                            name="fecha_proceso"
                            value="{{ request('fecha_proceso') }}"
                        >
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn">Filtrar</button>

                    <a href="{{ route('latam.tracking.index') }}" class="btn btn-secondary">
                        Limpiar filtros
                    </a>
                </div>
            </form>
        </div>

        @if(isset($rows) && count($rows) > 0)
            <div class="results">
                <h2>Trackings almacenados</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Prefijo</th>
                            <th>Código</th>
                            <th>Destino</th>
                            <th>Fecha proceso</th>
                            <th>Tracking LATAM</th>
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
                                    <a href="{{ $row['url'] }}" target="_blank" class="tracking-link">
                                        Abrir tracking
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">
                No se encontraron registros con esos filtros.
            </div>
        @endif
    </div>
</body>
</html>