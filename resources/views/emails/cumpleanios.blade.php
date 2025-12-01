<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* ===== ESTILOS COMPATIBLES CON OUTLOOK ===== */
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

        .title-icon {
            font-size: 28px;
            margin-right: 6px;
        }

        p {
            font-size: 15px;
            color: #444;
            line-height: 1.6;
        }

        ul {
            padding-left: 18px;
            margin-top: 12px;
        }

        li {
            font-size: 15px;
            margin-bottom: 6px;
        }

        .card {
            background: #f8fbff;
            border: 1px solid #d8e7f7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
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

        <h2>
            <span class="title-icon">🎉</span>
            Empleados que cumplen años hoy
        </h2>

        <p>
            Hoy celebramos a nuestros compañeros que están de cumpleaños.  
            ¡Les deseamos un excelente día, lleno de alegría y buenos momentos!
        </p>

        <div class="card">
            <ul>
                @foreach ($cumpleanieros as $empleado)
                    <li>
                        <strong>{{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }}</strong>
                        — {{ $empleado->FechaNacimiento->format('d/m') }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="footer">
            Equipo de 4Nortes
        </div>

    </div>

</body>
</html>
