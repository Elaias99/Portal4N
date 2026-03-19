<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f4f6f8;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e2e2e2;
            padding: 25px;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #0d1a2d;
        }

        p {
            font-size: 15px;
            color: #444;
            line-height: 1.6;
        }

        .card {
            background: #f8fbff;
            border: 1px solid #d8e7f7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .item {
            margin-bottom: 10px;
            font-size: 14px;
            color: #333;
        }

        .label {
            font-weight: bold;
            color: #0d1a2d;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>✅ Backup de base de datos generado correctamente</h2>

        <p>
            Se ha generado correctamente un nuevo respaldo automático de la base de datos del sistema.
        </p>

        <div class="card">
            {{-- <div class="item">
                <span class="label">Archivo:</span>
                {{ $nombreArchivo }}
            </div> --}}

            {{-- <div class="item">
                <span class="label">Ruta:</span>
                {{ $rutaArchivo }}
            </div> --}}

            <div class="item">
                <span class="label">Fecha de generación:</span>
                {{ $fechaGeneracion }}
            </div>
        </div>

        <p>
            Este correo fue enviado automáticamente por Portal4N.
        </p>

        <div class="footer">
            Equipo de 4Nortes
        </div>
    </div>
</body>
</html>