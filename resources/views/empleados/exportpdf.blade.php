{{-- <!DOCTYPE html>
<html>
<head>
    <title>Cotización del Trabajador</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            margin: 20px 0;
        }
        .content p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cotización del Trabajador</h1>
        </div>

        <div class="content">
            <p><strong>Nombre:</strong> {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}</p>
            <p><strong>RUT:</strong> {{ $trabajador->Rut }}</p>
            <p><strong>Salario Bruto:</strong> {{ $trabajador->salario_bruto }}</p>

            @if($cotizacion)
                <p><strong>Cotización Obligatoria:</strong> {{ $cotizacion['cotizacion'] }}</p>
                <p><strong>SIS:</strong> {{ $cotizacion['sis'] }}</p>
                <p><strong>Total Descuento:</strong> {{ $cotizacion['total'] }}</p>
            @else
                <p><strong>Cotización:</strong> No disponible</p>
            @endif
        </div>
    </div>
</body>
</html> --}}


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidación de Remuneraciones</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #000;
            margin: 20px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .header-container {
            width: 100%;
            border: 1px solid black;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .header-container .title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .header-container table {
            width: 100%;
        }
        .header-container table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .content-container {
            border: 1px solid black;
            padding: 15px;
            border-radius: 5px;
            box-sizing: border-box;
            margin-bottom: 20px;
        }
        .content-container div {
            margin-bottom: 10px;
        }
        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .signature .line {
            width: 40%;
            border-top: 1px solid black;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="Logo de la Empresa" style="max-height: 100px;">
        </div>
        <div class="header-container">
            <div class="title">
                LIQUIDACIÓN DE REMUNERACIONES<br>
                {{ \Carbon\Carbon::now()->translatedFormat('F - Y') }}
            </div>
            
            <table>
                <tr>
                    <td><strong>Empleador:</strong> 4 NORTES LOGÍSTICA SPA</td>
                    <td><strong>RUT:</strong> 77.346.078-7</td>
                </tr>
                <tr>
                    <td><strong>Trabajador:</strong> {{ strtoupper($trabajador->ApellidoMaterno) }} {{ strtoupper($trabajador->ApellidoPaterno) }}, {{ strtoupper($trabajador->Nombre) }} {{ strtoupper($trabajador->SegundoNombre) }}</td>
                    <td><strong>RUT:</strong> {{ $trabajador->Rut }}</td>
                </tr>
                <tr>
                    <td><strong>Cargo:</strong> {{ $trabajador->cargo->Nombre }}</td>
                    <td></td>
                </tr>
            </table>
        </div>

        <div class="content-container">
            <div>
                <label>Salario Bruto:</label>
                <span>{{ '$' . number_format($trabajador->salario_bruto, 0, ',', '.') }}</span>
            </div>
            <div>
                <label>AFP:</label>
                <span>{{ $trabajador->afp->Nombre }}</span>
            </div>
            <div>
                <label>Asignación Familiar:</label>
                <span>{{ '$' . number_format($trabajador->calcularAsignacionFamiliar(), 0, ',', '.') }}</span>
            </div>
            @if($cotizacion)
                <div>
                    <label>Cotización Obligatoria:</label>
                    <span>{{ '$' . number_format($cotizacion['cotizacion'], 0, ',', '.') }}</span>
                </div>
                <div>
                    <label>SIS:</label>
                    <span>{{ '$' . number_format($cotizacion['sis'], 0, ',', '.') }}</span>
                </div>
                <div>
                    <label>Total Descuento:</label>
                    <span>{{ '$' . number_format($cotizacion['total'], 0, ',', '.') }}</span>
                </div>
            @else
                <p style="color: red;"><strong>Cotización:</strong> No disponible</p>
            @endif
        </div>

    </div>
</body>
</html>
