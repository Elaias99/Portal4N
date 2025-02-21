<!DOCTYPE html>
<html>
<head>
    <title>Listado de Empleados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            margin: 5px;
            position: relative;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        .logo {
            text-align: left;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 100px;
            max-height: 100px;
        }
        .watermark {
            position: absolute;
            top: 60%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.5; /* Ajusta la opacidad para que se vea como una marca de agua */
            width: 500px; /* Ajusta el tamaño según sea necesario */
            height: auto;
            z-index: -1; /* Asegúrate de que la imagen esté detrás del texto */
        }
    </style>
</head>
<body>
    <div class="logo">
        <img src="{{ public_path('images/logo.png') }}" alt="Logo">
    </div>

    <div class="header">
        <h1>Listado de Empleados</h1>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">Rut</th>
                <th style="width: 100px;">Nombre Completo</th>
       
                <th style="width: 60px;">Empresa</th>
                <th style="width: 100px;">Cargo</th>
                <th style="width: 80px;">AFP</th>
                <th style="width: 80px;">Comuna</th>
                <th style="width: 80px;">Salud</th>
                <th style="width: 40px;">Casino</th>
                <th style="width: 60px;">Estado Civil</th>
                <th style="width: 40px;">Contrato Firmado</th>
                <th style="width: 40px;">Anexo Contrato</th>
                <th style="width: 60px;">Situación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($empleados as $empleado)
                <tr>
                    <td>{{ $empleado->Rut }}</td>
                    <td>{{ $empleado->Nombre }} {{ $empleado->SegundoNombre }} {{ $empleado->TercerNombre }} {{ $empleado->ApellidoPaterno }} {{ $empleado->ApellidoMaterno }}</td>

                    <td>{{ $empleado->empresa->Nombre }}</td>
                    <td>{{ $empleado->cargo->Nombre }}</td>
                    <td>{{ $empleado->afp->Nombre }}</td>
                    <td>{{ $empleado->comuna->Nombre }}</td>
                    <td>{{ $empleado->salud->Nombre }}</td>
                    <td>{{ $empleado->Casino }}</td>
                    <td>{{ $empleado->estadoCivil->Nombre }}</td>
                    <td>{{ $empleado->ContratoFirmado }}</td>
                    <td>{{ $empleado->AnexoContrato }}</td>
                    <td>{{ $empleado->situacion->Nombre }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>Generado el {{ date('d/m/Y') }}</p>
    </div>
    <img src="{{ public_path('images/auto.png') }}" class="watermark" alt="Watermark">
</body>
</html>
