<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Días</title>
    <style>
        body { font-family: Montserrat, sans-serif; font-size: 11px; margin: 0; padding: 0; }
        .page { padding: 10px; box-sizing: border-box; }
        .header { text-align: center; margin-bottom: 5px; }
        .logo { position: absolute; top: 5px; right: 10px; width: 100px; height: auto; }
        .title { text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .nro-solicitud { text-align: center; font-size: 11px; font-weight: bold; border: 1px solid black; width: 50px; padding: 2px; margin: 0 auto; border-radius: 8px; }
        .info-table { width: 100%; margin-bottom: 5px; }
        .info-table td { padding: 2px 0; }
        .signature-section { margin-top: 10px; text-align: center; }
        .signature img { width: 150px; height: auto; margin-bottom: 5px; }
        .divider { border-top: 1px dashed black; margin: 5px 0; }
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

        <p>El trabajador hará uso de 
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

        <br>
        <br>
        <br>
        
        <div class="signature-section" style="display: flex; flex-direction: column; align-items: center; gap: 7px;">
            <div class="signature" style="font-size: 15px; margin-bottom: 7px;">
                _______________________________ <br>
                {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}<br>
                {{ $solicitud->trabajador->Rut }}
            </div>
        
            <div class="signature" style="margin-top: 5px;">
                @if(isset($firmaPath) && file_exists($firmaPath))
                    <img src="{{ $firmaPath }}" alt="Firma del Jefe" style="width: 120px; height: auto; margin-bottom: 3px;">
                @else
                    _______________________________ <br>
                @endif
                <span style="display: block; text-align: center; font-size: 12px; margin-top: 2px;">Jefatura Directa</span>
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

        <p>El trabajador hará uso de 
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

        <br>
        <br>
        <br>

        <div class="signature-section" style="display: flex; flex-direction: column; align-items: center; gap: 7px;">
            <div class="signature" style="font-size: 15px; margin-bottom: 7px;">
                _______________________________ <br>
                {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}<br>
                {{ $solicitud->trabajador->Rut }}
            </div>
        
            <div class="signature" style="margin-top: 5px;">
                @if(isset($firmaPath) && file_exists($firmaPath))
                    <img src="{{ $firmaPath }}" alt="Firma del Jefe" style="width: 120px; height: auto; margin-bottom: 3px;">
                @else
                    _______________________________ <br>
                @endif
                <span style="display: block; text-align: center; font-size: 12px; margin-top: 2px;">Jefatura Directa</span>
            </div>
        </div>


    </div>

</div>

</body>
</html>
