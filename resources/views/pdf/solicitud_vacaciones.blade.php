<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Días</title>
    <style>
        /* Configuración de la página A4 con márgenes */
        @page {
            size: A4;
            margin: 20mm; /* Ajusta según prefieras */
        }

        /* Ajustes generales */
        body {
            font-family: Arial, sans-serif;
            font-size: 14px; /* Incrementamos para mayor legibilidad */
            margin: 0;
            padding: 0;
        }

        .page {
            /* El padding interno de la hoja */
            padding: 20px;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            position: relative; /* para posicionar el logo dentro de .header */
        }

        /* Ajustamos tamaño del logo para mayor visibilidad */
        .logo {
            position: absolute;
            top: 0;   /* Ajusta si deseas que baje un poco */
            right: 0;
            width: 110px; /* Ajustado para ser más grande */
            height: auto;
        }

        /* Título principal */
        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .nro-solicitud {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            border: 2px solid black;
            width: 60px; /* Ajusta ancho según prefieras */
            padding: 3px;
            margin: 0 auto;
            border-radius: 10px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse; /* Para minimizar espacios */
        }

        .info-table td {
            padding: 3px 0; /* Reducimos padding vertical */
        }

        .signature-section {
            margin-top: 20px; 
            text-align: center;
            display: flex; 
            flex-direction: column; 
            align-items: center;
        }

        .signature {
            margin-top: 20px;
            text-align: center;
        }

        .signature img {
            width: 135px; /* Aumentamos para que se vea más grande */
            height: auto;
            margin-bottom: 5px;
            background: none; /* si tu imagen es transparente, no se verá un fondo */
        }

        .divider {
            border-top: 1px dashed black;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<div class="page">

    <!-- Primera copia -->
    <div class="header">
        <img src="{{ asset('storage/' . $solicitud->trabajador->empresa->logo) }}" alt="Logo de la Empresa" class="logo">
        <div class="title">

            SOLICITUD DE {{ strtoupper(
                $solicitud->tipo_dia === 'vacaciones' ? 'Vacaciones' : 
                ($solicitud->tipo_dia === 'administrativo' ? 'Días Administrativos' : 
                ($solicitud->tipo_dia === 'sin_goce_de_sueldo' ? 'Permiso sin goce de sueldo' : 
                ($solicitud->tipo_dia === 'permiso_fuerza_mayor' ? 'Permiso fuerza mayor' : 
                'Licencia Médica')))
            ) }}
            
        </div>
        <div class="nro-solicitud">
            Nro: {{ $solicitud->vacacion->id }}
        </div>
    </div>

    <div class="container">
        <table class="info-table">
            <tr>
                <td><strong>Trabajador:</strong></td>
                <td>{{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }} {{ $solicitud->trabajador->ApellidoMaterno }}</td>
            </tr>
            <tr>
                <td><strong>RUT:</strong></td>
                <td>{{ $solicitud->trabajador->Rut }}</td>
            </tr>
            <tr>
                <td><strong>Cargo:</strong></td>
                <td>{{ $solicitud->trabajador->cargo->Nombre }}</td>
            </tr>
            <tr>
                <td><strong>Fecha Contrato:</strong></td>
                <td>{{ \Carbon\Carbon::parse($solicitud->trabajador->fecha_inicio_contrato)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Fecha Solicitud:</strong></td>
                <td>{{ $solicitud->created_at->format('d/m/Y') }}</td>
            </tr>
        </table>

        <p>
            El trabajador hará uso de 
            @if($solicitud->tipo_dia === 'vacaciones')
                {{ $solicitud->vacacion->dias }} días hábiles legales por vacaciones,
            @elseif($solicitud->tipo_dia === 'administrativo')
                {{ $solicitud->vacacion->dias }} días administrativos (sin descuento en días de vacaciones),
            @elseif($solicitud->tipo_dia === 'sin_goce_de_sueldo')
                {{ $solicitud->vacacion->dias }} días por permiso sin goce de sueldo,
            @elseif($solicitud->tipo_dia === 'permiso_fuerza_mayor')
                {{ $solicitud->vacacion->dias }} días por permiso de fuerza mayor,
            @elseif($solicitud->tipo_dia === 'licencia_medica')
                {{ $solicitud->vacacion->dias }} días por Licencia Médica,
            @endif
            desde el {{ \Carbon\Carbon::parse($solicitud->vacacion->fecha_inicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($solicitud->vacacion->fecha_fin)->format('d/m/Y') }}.
        </p>

        <div class="signature-section">
            <div class="signature">
                _______________________________ <br>
                {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}<br>
                {{ $solicitud->trabajador->Rut }}
            </div>

            <div class="signature" style="margin-top: 10px;">
                @if(isset($firmaPath) && file_exists($firmaPath))
                    <img src="{{ $firmaPath }}" alt="Firma del Jefe">
                @else
                    _______________________________ <br>
                @endif
                <span style="display: block; text-align: center;">Jefatura Directa</span>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Segunda copia (idéntica a la primera) -->
    <div class="header">
        <img src="{{ asset('storage/' . $solicitud->trabajador->empresa->logo) }}" alt="Logo de la Empresa" class="logo">
        <div class="title">

            SOLICITUD DE {{ strtoupper(
                $solicitud->tipo_dia === 'vacaciones' ? 'Vacaciones' : 
                ($solicitud->tipo_dia === 'administrativo' ? 'Días Administrativos' : 
                ($solicitud->tipo_dia === 'sin_goce_de_sueldo' ? 'Permiso sin goce de sueldo' : 
                ($solicitud->tipo_dia === 'permiso_fuerza_mayor' ? 'Permiso fuerza mayor' : 
                'Licencia Médica')))
            ) }}
            
        </div>
        <div class="nro-solicitud">
            Nro: {{ $solicitud->vacacion->id }}
        </div>
    </div>

    <div class="container">
        <table class="info-table">
            <tr>
                <td><strong>Trabajador:</strong></td>
                <td>{{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }} {{ $solicitud->trabajador->ApellidoMaterno }}</td>
            </tr>
            <tr>
                <td><strong>RUT:</strong></td>
                <td>{{ $solicitud->trabajador->Rut }}</td>
            </tr>
            <tr>
                <td><strong>Cargo:</strong></td>
                <td>{{ $solicitud->trabajador->cargo->Nombre }}</td>
            </tr>
            <tr>
                <td><strong>Fecha Contrato:</strong></td>
                <td>{{ \Carbon\Carbon::parse($solicitud->trabajador->fecha_inicio_contrato)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Fecha Solicitud:</strong></td>
                <td>{{ $solicitud->created_at->format('d/m/Y') }}</td>
            </tr>
        </table>

        <p>
            El trabajador hará uso de 
            @if($solicitud->tipo_dia === 'vacaciones')
                {{ $solicitud->vacacion->dias }} días hábiles legales por vacaciones,
            @elseif($solicitud->tipo_dia === 'administrativo')
                {{ $solicitud->vacacion->dias }} días administrativos (sin descuento en días de vacaciones),
            @elseif($solicitud->tipo_dia === 'sin_goce_de_sueldo')
                {{ $solicitud->vacacion->dias }} días por permiso sin goce de sueldo,
            @elseif($solicitud->tipo_dia === 'permiso_fuerza_mayor')
                {{ $solicitud->vacacion->dias }} días por permiso de fuerza mayor,
            @elseif($solicitud->tipo_dia === 'licencia_medica')
                {{ $solicitud->vacacion->dias }} días por Licencia Médica,
            @endif
            desde el {{ \Carbon\Carbon::parse($solicitud->vacacion->fecha_inicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($solicitud->vacacion->fecha_fin)->format('d/m/Y') }}.
        </p>

        <div class="signature-section">
            <div class="signature">
                _______________________________ <br>
                {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}<br>
                {{ $solicitud->trabajador->Rut }}
            </div>

            <div class="signature" style="margin-top: 10px;">
                @if(isset($firmaPath) && file_exists($firmaPath))
                    <img src="{{ $firmaPath }}" alt="Firma del Jefe">
                @else
                    _______________________________ <br>
                @endif
                <span style="display: block; text-align: center;">Jefatura Directa</span>
            </div>
        </div>
    </div>

</div>

</body>
</html>
