<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas diarias</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #eef1f4;
            font-family: Arial, Helvetica, sans-serif;
            color: #333333;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        .wrapper {
            width: 100%;
            background: #eef1f4;
            padding: 20px 10px;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 6px;
        }

        .content {
            padding: 24px;
        }

        h2 {
            margin: 0 0 15px;
            font-size: 22px;
            line-height: 1.3;
            color: #0d1a2d;
            font-weight: bold;
        }

        p {
            margin: 0 0 16px;
            line-height: 1.6;
            font-size: 15px;
            white-space: pre-line;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            margin-top: 15px;
        }

        .reservas-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .reservas-table th {
            background: #f2f2f2;
            padding: 8px;
            border: 1px solid #cccccc;
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
        }

        .reservas-table td {
            padding: 8px;
            border: 1px solid #cccccc;
            vertical-align: top;
        }

        .footer {
            margin-top: 28px;
            text-align: center;
            font-size: 14px;
            color: #555555;
        }

        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 12px 6px;
            }

            .content {
                padding: 16px;
            }

            h2 {
                font-size: 18px;
            }

            p {
                font-size: 14px;
                line-height: 1.5;
            }

            .reservas-table {
                font-size: 12px;
            }

            .reservas-table th,
            .reservas-table td {
                padding: 6px;
            }

            .footer {
                font-size: 13px;
                margin-top: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <div class="content">

                <h2>📦 Reservas diarias – 4N Logística</h2>

                <p>{!! nl2br(e($mensaje)) !!}</p>

                <div class="table-wrap">
                    <table class="reservas-table" role="presentation">
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
                            <tr><td>SCL ISLA DE PASCUA</td><td>10</td><td>NN</td><td>---</td><td>CARGA GENERAL</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="footer">
                    Equipo de 4Nortes
                </div>

            </div>
        </div>
    </div>
</body>
</html>