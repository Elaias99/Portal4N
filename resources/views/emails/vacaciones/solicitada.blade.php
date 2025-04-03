<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Vacaciones</title>
</head>
<body>
    <h2>📅 Nueva Solicitud de Vacaciones</h2>

    <p><strong>Empleado:</strong> {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}</p>
    <p><strong>Periodo:</strong>
        {{ \Carbon\Carbon::parse($solicitud->vacacion->fecha_inicio)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
        al
        {{ \Carbon\Carbon::parse($solicitud->vacacion->fecha_fin)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
    </p>    
    <p><strong>Días solicitados:</strong> {{ $solicitud->vacacion->dias }}</p>
    <p><strong>Tipo:</strong> {{ ucfirst(str_replace('_', ' ', $solicitud->tipo_dia)) }}</p>

    <hr>
    <p>Por favor ingrese al sistema para aprobar o rechazar esta solicitud.</p>
</body>
</html>
