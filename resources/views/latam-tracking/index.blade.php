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
            max-width: 1000px;
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
        }

        .btn-secondary {
            background: #6c757d;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>LATAM Tracking</h1>
        <p>Aquí se muestran los códigos almacenados para consulta y seguimiento.</p>

        @if(isset($rows) && count($rows) > 0)
            <div class="results">
                <h2>Trackings almacenados</h2>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
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
                                <td>{{ $row['id'] }}</td>
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
                No hay trackings almacenados todavía.
            </div>
        @endif
    </div>
</body>
</html>