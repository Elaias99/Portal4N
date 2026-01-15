<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Informe Mensual Tracking</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        h1 {
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>

<h1>Informe Mensual – Uso del Tracking</h1>

<p><strong>Período:</strong> {{ $month }}/{{ $year }}</p>

<table>
    <tr>
        <th>Métrica</th>
        <th>Valor</th>
    </tr>
    <tr>
        <td>Total de consultas</td>
        <td>{{ $totalSearches }}</td>
    </tr>
    <tr>
        <td>Envíos distintos consultados</td>
        <td>{{ $uniqueTrackings }}</td>
    </tr>
    <tr>
        <td>Consultas de envíos entregados</td>
        <td>{{ $deliveredCount }}</td>
    </tr>
    <tr>
        <td>Consultas de envíos pendientes</td>
        <td>{{ $pendingCount }}</td>
    </tr>
    <tr>
        <td>Envíos con prueba de entrega</td>
        <td>{{ $withProof }}</td>
    </tr>
</table>

</body>
</html>
