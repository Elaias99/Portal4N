<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #eef1f4;
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 20px auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        h2 {
            margin: 0 0 15px;
            font-size: 22px;
            color: #0d1a2d;
            font-weight: bold;
        }

        p {
            line-height: 1.6;
            font-size: 15px;
            white-space: pre-line;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }

        table th {
            background: #f2f2f2;
            padding: 8px;
            border: 1px solid #ccc;
            text-align: left;
        }

        table td {
            padding: 8px;
            border: 1px solid #ccc;
        }

        .footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">

        <h2>📦 Reservas diarias – 4N Logística</h2>

        <p>
            {!! nl2br(e($mensaje)) !!}
        </p>

        {{-- Tabla plana reemplazando el texto tabulado --}}
        <table>
            <thead>
                <tr>
                    <th>Destino</th>
                    <th>KG Aprox</th>
                    <th>Vuelo</th>
                    <th>Estándar</th>
                    <th>Tipo de carga</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>SCL ARICA</td><td>10</td><td>NN</td><td>---</td><td>CARGA GENERAL</td></tr>
                <tr><td>SCL IQUIQUE</td><td>10</td><td>NN</td><td>---</td><td>CARGA GENERAL</td></tr>
                <tr><td>SCL ANTOFAGASTA</td><td>10</td><td>NN</td><td>---</td><td>CARGA GENERAL</td></tr>
                <tr><td>SCL CALAMA</td><td>10</td><td>NN</td><td>---</td><td>CARGA GENERAL</td></tr>
                <tr><td>SCL PUNTA ARENAS</td><td>10</td><td>NN</td><td>---</td><td>CARGA GENERAL</td></tr>
                <tr><td>SCL BALMACEDA</td><td>10</td><td>NN</td><td>---</td><td>CARGA GENERAL</td></tr>
            </tbody>
        </table>

        <div class="footer" style="text-align:center; margin-top:28px; font-size:14px; color:#555;">
            Equipo de 4Nortes
        </div>



    </div>
</body>
</html>
