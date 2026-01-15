<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Links de Prueba de Entrega</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            background: #f7f7f7;
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #efefef;
        }

        button {
            padding: 6px 10px;
            cursor: pointer;
        }

        .empty {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<h1>Envíos con prueba de entrega</h1>

@if ($trackings->isEmpty())
    <div class="empty">
        No existen envíos con prueba de entrega aún.
    </div>
@else
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tracking</th>
                <th>Estado</th>
                <th>Fotos</th>
                <th>Fecha</th>
                <th>Link</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trackings as $row)
                <tr>
                    <td>{{ $row['id'] }}</td>

                    <td>{{ $row['tracking'] }}</td>
                    <td>{{ ucfirst($row['state']) }}</td>
                    <td>{{ $row['photos'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>
                        @if ($row['link'])
                            <a href="{{ $row['link'] }}" target="_blank">
                                {{ $row['link'] }}
                            </a>
                        @else
                            —
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<script>
    function copyLink(link) {
        navigator.clipboard.writeText(link).then(function () {
            alert('Link copiado');
        });
    }
</script>

</body>
</html>
